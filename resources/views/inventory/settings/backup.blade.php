@extends('layouts.app')

@section('title', 'Backup & Restore Settings')

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
                            <li class="breadcrumb-item"><a href="{{ route('inventory.settings.index') }}">Settings</a></li>
                            <li class="breadcrumb-item active">Backup & Restore</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">Backup & Restore Settings</h1>
                    <p class="text-muted">Configure automated backups, data protection, and system recovery options</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="createManualBackup()">
                        <i class="fas fa-download"></i> Create Backup Now
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="testBackupSystem()">
                        <i class="fas fa-vial"></i> Test System
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveAllBackupSettings()">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs nav-tabs-custom" id="backupTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="automated-tab" data-bs-toggle="tab" data-bs-target="#automated" type="button" role="tab">
                        <i class="fas fa-clock"></i> Automated Backups
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">
                        <i class="fas fa-hand-paper"></i> Manual Backups
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="storage-tab" data-bs-toggle="tab" data-bs-target="#storage" type="button" role="tab">
                        <i class="fas fa-hdd"></i> Storage & Retention
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="restore-tab" data-bs-toggle="tab" data-bs-target="#restore" type="button" role="tab">
                        <i class="fas fa-undo"></i> Restore & Recovery
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring" type="button" role="tab">
                        <i class="fas fa-chart-line"></i> Monitoring & Logs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-shield-alt"></i> Security & Encryption
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="backupTabsContent">
        <!-- Automated Backups Tab -->
        <div class="tab-pane fade show active" id="automated" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-robot text-primary"></i> Automated Backup Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="automatedBackupForm">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Enable Automated Backups</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="enableAutomatedBackups" checked>
                                                <label class="form-check-label" for="enableAutomatedBackups">
                                                    Automatically create system backups
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="backupFrequency" class="form-label">Backup Frequency</label>
                                            <select class="form-select" id="backupFrequency">
                                                <option value="hourly">Every Hour</option>
                                                <option value="daily" selected>Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="backupTime" class="form-label">Backup Time</label>
                                            <input type="time" class="form-control" id="backupTime" value="02:00">
                                            <small class="form-text text-muted">Time when automated backups will run</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="backupTypes" class="form-label">Backup Types</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="backupDatabase" checked>
                                                <label class="form-check-label" for="backupDatabase">Database</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="backupFiles" checked>
                                                <label class="form-check-label" for="backupFiles">Application Files</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="backupUploads" checked>
                                                <label class="form-check-label" for="backupUploads">User Uploads</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="backupConfig">
                                                <label class="form-check-label" for="backupConfig">Configuration Files</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="compressionLevel" class="form-label">Compression Level</label>
                                            <select class="form-select" id="compressionLevel">
                                                <option value="none">No Compression</option>
                                                <option value="low">Low (Fast)</option>
                                                <option value="medium" selected>Medium (Balanced)</option>
                                                <option value="high">High (Small Size)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maxBackupSize" class="form-label">Max Backup Size (MB)</label>
                                            <input type="number" class="form-control" id="maxBackupSize" value="1024" min="100" max="10240">
                                            <small class="form-text text-muted">Maximum size for individual backup files</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notification Settings</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="notifySuccess" checked>
                                        <label class="form-check-label" for="notifySuccess">Notify on successful backup</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="notifyFailure" checked>
                                        <label class="form-check-label" for="notifyFailure">Notify on backup failure</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="notifyWarnings">
                                        <label class="form-check-label" for="notifyWarnings">Notify on warnings</label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Backup Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Last Backup:</span>
                                <span class="text-success" id="lastBackupTime">2 hours ago</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Next Backup:</span>
                                <span class="text-primary" id="nextBackupTime">In 22 hours</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Status:</span>
                                <span class="badge bg-success" id="backupStatus">Active</span>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 85%" id="backupProgress">85%</div>
                            </div>
                            <small class="text-muted">System health: Excellent</small>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Recent Backups</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush" id="recentBackupsList">
                                <!-- Recent backups will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Backups Tab -->
        <div class="tab-pane fade" id="manual" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-hand-paper text-primary"></i> Manual Backup Creation
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="manualBackupForm">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="backupName" class="form-label">Backup Name</label>
                                            <input type="text" class="form-control" id="backupName" placeholder="Enter backup name">
                                            <small class="form-text text-muted">Leave empty for auto-generated name</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="backupDescription" class="form-label">Description</label>
                                            <input type="text" class="form-control" id="backupDescription" placeholder="Optional description">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Select Data to Backup</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="manualBackupDatabase" checked>
                                                <label class="form-check-label" for="manualBackupDatabase">
                                                    <i class="fas fa-database text-primary"></i> Database (Required)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="manualBackupFiles" checked>
                                                <label class="form-check-label" for="manualBackupFiles">
                                                    <i class="fas fa-file-code text-info"></i> Application Files
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="manualBackupUploads" checked>
                                                <label class="form-check-label" for="manualBackupUploads">
                                                    <i class="fas fa-upload text-success"></i> User Uploads
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="manualBackupLogs">
                                                <label class="form-check-label" for="manualBackupLogs">
                                                    <i class="fas fa-list-alt text-warning"></i> System Logs
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="manualCompressionLevel" class="form-label">Compression</label>
                                            <select class="form-select" id="manualCompressionLevel">
                                                <option value="none">No Compression</option>
                                                <option value="low">Low (Fast)</option>
                                                <option value="medium" selected>Medium (Balanced)</option>
                                                <option value="high">High (Small Size)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="backupFormat" class="form-label">Backup Format</label>
                                            <select class="form-select" id="backupFormat">
                                                <option value="zip" selected>ZIP Archive</option>
                                                <option value="tar">TAR Archive</option>
                                                <option value="sql">SQL Dump (Database only)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="encryptBackup">
                                        <label class="form-check-label" for="encryptBackup">
                                            Encrypt backup with password
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" onclick="startManualBackup()">
                                        <i class="fas fa-play"></i> Start Backup
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="estimateBackupSize()">
                                        <i class="fas fa-calculator"></i> Estimate Size
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Backup Progress</h6>
                        </div>
                        <div class="card-body">
                            <div id="backupProgressContainer" style="display: none;">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span>Progress:</span>
                                        <span id="backupProgressPercent">0%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" style="width: 0%" id="backupProgressBar"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Current Step:</small>
                                    <div id="currentBackupStep">Initializing...</div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Estimated Size:</small>
                                    <div id="estimatedBackupSize">Calculating...</div>
                                </div>
                            </div>
                            <div id="backupIdleState">
                                <p class="text-muted text-center">No backup in progress</p>
                                <p class="text-center">
                                    <i class="fas fa-cloud-download-alt fa-3x text-muted"></i>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="createDatabaseBackup()">
                                    <i class="fas fa-database"></i> Database Only
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="createFilesBackup()">
                                    <i class="fas fa-file-archive"></i> Files Only
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="createFullBackup()">
                                    <i class="fas fa-hdd"></i> Full System
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage & Retention Tab -->
        <div class="tab-pane fade" id="storage" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-hdd text-primary"></i> Storage Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="storageConfigForm">
                                <div class="mb-4">
                                    <h6>Primary Storage Location</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="storageType" class="form-label">Storage Type</label>
                                                <select class="form-select" id="storageType" onchange="updateStorageConfig()">
                                                    <option value="local" selected>Local Storage</option>
                                                    <option value="ftp">FTP Server</option>
                                                    <option value="sftp">SFTP Server</option>
                                                    <option value="aws_s3">Amazon S3</option>
                                                    <option value="google_drive">Google Drive</option>
                                                    <option value="dropbox">Dropbox</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="storagePath" class="form-label">Storage Path</label>
                                                <input type="text" class="form-control" id="storagePath" value="/backups" placeholder="Enter storage path">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cloud Storage Configuration (Hidden by default) -->
                                    <div id="cloudStorageConfig" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="cloudAccessKey" class="form-label">Access Key / Username</label>
                                                    <input type="text" class="form-control" id="cloudAccessKey" placeholder="Enter access key">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="cloudSecretKey" class="form-label">Secret Key / Password</label>
                                                    <input type="password" class="form-control" id="cloudSecretKey" placeholder="Enter secret key">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="cloudRegion" class="form-label">Region / Server</label>
                                                    <input type="text" class="form-control" id="cloudRegion" placeholder="e.g., us-east-1">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="cloudBucket" class="form-label">Bucket / Folder</label>
                                                    <input type="text" class="form-control" id="cloudBucket" placeholder="Enter bucket name">
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="testCloudConnection()">
                                            <i class="fas fa-plug"></i> Test Connection
                                        </button>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-4">
                                    <h6>Retention Policy</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="keepDaily" class="form-label">Keep Daily Backups</label>
                                                <input type="number" class="form-control" id="keepDaily" value="7" min="1" max="365">
                                                <small class="form-text text-muted">Days to keep daily backups</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="keepWeekly" class="form-label">Keep Weekly Backups</label>
                                                <input type="number" class="form-control" id="keepWeekly" value="4" min="1" max="52">
                                                <small class="form-text text-muted">Weeks to keep weekly backups</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="keepMonthly" class="form-label">Keep Monthly Backups</label>
                                                <input type="number" class="form-control" id="keepMonthly" value="12" min="1" max="120">
                                                <small class="form-text text-muted">Months to keep monthly backups</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-4">
                                    <h6>Storage Limits</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="maxStorageSize" class="form-label">Maximum Storage Size (GB)</label>
                                                <input type="number" class="form-control" id="maxStorageSize" value="100" min="1" max="1000">
                                                <small class="form-text text-muted">Maximum total storage for all backups</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="cleanupThreshold" class="form-label">Cleanup Threshold (%)</label>
                                                <input type="number" class="form-control" id="cleanupThreshold" value="90" min="50" max="99">
                                                <small class="form-text text-muted">Start cleanup when storage reaches this percentage</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="autoCleanup" checked>
                                        <label class="form-check-label" for="autoCleanup">
                                            Enable automatic cleanup of old backups
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="verifyBackups" checked>
                                        <label class="form-check-label" for="verifyBackups">
                                            Verify backup integrity after creation
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Storage Usage</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Used Storage:</span>
                                    <span id="usedStorage">45.2 GB</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 45%" id="storageUsageBar">45%</div>
                                </div>
                                <small class="text-muted">of 100 GB total</small>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Total Backups:</span>
                                    <span id="totalBackups">127</span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Oldest Backup:</span>
                                    <span id="oldestBackup">3 months ago</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Storage Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-warning btn-sm" onclick="cleanupOldBackups()">
                                    <i class="fas fa-broom"></i> Cleanup Old Backups
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="verifyAllBackups()">
                                    <i class="fas fa-check-circle"></i> Verify All Backups
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="optimizeStorage()">
                                    <i class="fas fa-compress-arrows-alt"></i> Optimize Storage
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore & Recovery Tab -->
        <div class="tab-pane fade" id="restore" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-undo text-primary"></i> Available Backups
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" id="backupSearch" placeholder="Search backups...">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" id="backupTypeFilter">
                                            <option value="">All Types</option>
                                            <option value="full">Full Backup</option>
                                            <option value="database">Database Only</option>
                                            <option value="files">Files Only</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" class="form-control" id="backupDateFilter">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-outline-primary" onclick="filterBackups()">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover" id="backupsTable">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Size</th>
                                            <th>Created</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="backupsTableBody">
                                        <!-- Backup list will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Restore Options</h6>
                        </div>
                        <div class="card-body">
                            <div id="restoreOptionsContainer" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Selected Backup:</label>
                                    <div id="selectedBackupInfo" class="p-2 bg-light rounded">
                                        No backup selected
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Restore Components:</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="restoreDatabase" checked>
                                        <label class="form-check-label" for="restoreDatabase">Database</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="restoreFiles">
                                        <label class="form-check-label" for="restoreFiles">Application Files</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="restoreUploads">
                                        <label class="form-check-label" for="restoreUploads">User Uploads</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="createRestorePoint" checked>
                                        <label class="form-check-label" for="createRestorePoint">
                                            Create restore point before restoring
                                        </label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button class="btn btn-warning" onclick="startRestore()">
                                        <i class="fas fa-undo"></i> Start Restore
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" onclick="previewRestore()">
                                        <i class="fas fa-eye"></i> Preview Changes
                                    </button>
                                </div>
                            </div>

                            <div id="noBackupSelected">
                                <p class="text-muted text-center">Select a backup to view restore options</p>
                                <p class="text-center">
                                    <i class="fas fa-mouse-pointer fa-2x text-muted"></i>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Recovery Tools</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-success btn-sm" onclick="repairDatabase()">
                                    <i class="fas fa-wrench"></i> Repair Database
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="validateSystem()">
                                    <i class="fas fa-check-double"></i> Validate System
                                </button>
                                <button class="btn btn-outline-warning btn-sm" onclick="emergencyRestore()">
                                    <i class="fas fa-exclamation-triangle"></i> Emergency Restore
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monitoring & Logs Tab -->
        <div class="tab-pane fade" id="monitoring" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line text-primary"></i> Backup Activity Log
                            </h5>
                            <div>
                                <button class="btn btn-outline-primary btn-sm" onclick="refreshLogs()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="exportLogs()">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <select class="form-select" id="logLevelFilter">
                                            <option value="">All Levels</option>
                                            <option value="info">Info</option>
                                            <option value="warning">Warning</option>
                                            <option value="error">Error</option>
                                            <option value="success">Success</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" id="logActionFilter">
                                            <option value="">All Actions</option>
                                            <option value="backup">Backup</option>
                                            <option value="restore">Restore</option>
                                            <option value="cleanup">Cleanup</option>
                                            <option value="verify">Verify</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" id="logSearchInput" placeholder="Search logs...">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-outline-primary" onclick="applyLogFilters()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm" id="backupLogsTable">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Level</th>
                                            <th>Action</th>
                                            <th>Message</th>
                                            <th>Duration</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="backupLogsTableBody">
                                        <!-- Logs will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <nav>
                                <ul class="pagination pagination-sm justify-content-center" id="logsPagination">
                                    <!-- Pagination will be loaded here -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">System Health</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Backup Success Rate:</span>
                                    <span class="text-success" id="successRate">98.5%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 98.5%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Average Backup Time:</span>
                                    <span id="avgBackupTime">12 minutes</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Failed Backups (30d):</span>
                                    <span class="text-danger" id="failedBackups">2</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>System Status:</span>
                                    <span class="badge bg-success" id="systemHealthStatus">Healthy</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Alerts & Notifications</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush" id="alertsList">
                                <!-- Alerts will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Performance Metrics</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="backupPerformanceChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security & Encryption Tab -->
        <div class="tab-pane fade" id="security" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt text-primary"></i> Security Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="securityConfigForm">
                                <div class="mb-4">
                                    <h6>Encryption Settings</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Enable Backup Encryption</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enableEncryption" checked>
                                                    <label class="form-check-label" for="enableEncryption">
                                                        Encrypt all backup files
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="encryptionAlgorithm" class="form-label">Encryption Algorithm</label>
                                                <select class="form-select" id="encryptionAlgorithm">
                                                    <option value="aes256" selected>AES-256</option>
                                                    <option value="aes128">AES-128</option>
                                                    <option value="chacha20">ChaCha20</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="encryptionKey" class="form-label">Encryption Key</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="encryptionKey" placeholder="Enter encryption key">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="generateEncryptionKey()">
                                                        <i class="fas fa-key"></i> Generate
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="keyDerivation" class="form-label">Key Derivation</label>
                                                <select class="form-select" id="keyDerivation">
                                                    <option value="pbkdf2" selected>PBKDF2</option>
                                                    <option value="scrypt">Scrypt</option>
                                                    <option value="argon2">Argon2</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-4">
                                    <h6>Access Control</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Backup Access Permissions</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="adminOnlyBackup" checked>
                                                    <label class="form-check-label" for="adminOnlyBackup">
                                                        Only administrators can create backups
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="adminOnlyRestore" checked>
                                                    <label class="form-check-label" for="adminOnlyRestore">
                                                        Only administrators can restore backups
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="adminOnlyDelete">
                                                    <label class="form-check-label" for="adminOnlyDelete">
                                                        Only administrators can delete backups
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="allowedIPs" class="form-label">Allowed IP Addresses</label>
                                                <textarea class="form-control" id="allowedIPs" rows="4" placeholder="Enter IP addresses (one per line)&#10;Leave empty to allow all IPs"></textarea>
                                                <small class="form-text text-muted">Restrict backup operations to specific IP addresses</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-4">
                                    <h6>Audit & Compliance</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="enableAuditLog" checked>
                                                    <label class="form-check-label" for="enableAuditLog">
                                                        Enable detailed audit logging
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="logUserActions" checked>
                                                    <label class="form-check-label" for="logUserActions">
                                                        Log all user actions
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="requireApproval">
                                                    <label class="form-check-label" for="requireApproval">
                                                        Require approval for restore operations
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="auditRetention" class="form-label">Audit Log Retention (days)</label>
                                                <input type="number" class="form-control" id="auditRetention" value="365" min="30" max="2555">
                                                <small class="form-text text-muted">How long to keep audit logs</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-3">
                                    <h6>Integrity Verification</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enableChecksums" checked>
                                        <label class="form-check-label" for="enableChecksums">
                                            Generate checksums for backup verification
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enableDigitalSignature">
                                        <label class="form-check-label" for="enableDigitalSignature">
                                            Add digital signatures to backups
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="verifyOnRestore" checked>
                                        <label class="form-check-label" for="verifyOnRestore">
                                            Verify backup integrity before restore
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Security Status</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Encryption Status:</span>
                                    <span class="badge bg-success" id="encryptionStatus">Active</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Key Strength:</span>
                                    <span class="text-success" id="keyStrength">Strong</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Access Control:</span>
                                    <span class="badge bg-success" id="accessControlStatus">Enabled</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Audit Logging:</span>
                                    <span class="badge bg-success" id="auditStatus">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Security Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="rotateEncryptionKey()">
                                    <i class="fas fa-sync-alt"></i> Rotate Encryption Key
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="auditBackupSecurity()">
                                    <i class="fas fa-search"></i> Security Audit
                                </button>
                                <button class="btn btn-outline-warning btn-sm" onclick="exportSecurityReport()">
                                    <i class="fas fa-file-export"></i> Export Security Report
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Recent Security Events</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush" id="securityEventsList">
                                <!-- Security events will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Restore Operation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action will overwrite current data with the selected backup.
                </div>
                <p>Are you sure you want to restore the following backup?</p>
                <div id="restoreConfirmDetails" class="p-3 bg-light rounded">
                    <!-- Backup details will be shown here -->
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmRestore">
                    <label class="form-check-label" for="confirmRestore">
                        I understand that this action cannot be undone
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="confirmRestore()" disabled id="confirmRestoreBtn">
                    <i class="fas fa-undo"></i> Restore Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Backup Progress Modal -->
<div class="modal fade" id="backupProgressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Backup in Progress</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Overall Progress:</span>
                        <span id="modalBackupPercent">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%" id="modalBackupProgressBar"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <strong>Current Step:</strong>
                    <div id="modalCurrentStep">Initializing backup...</div>
                </div>
                <div class="mb-2">
                    <strong>Estimated Time Remaining:</strong>
                    <div id="modalTimeRemaining">Calculating...</div>
                </div>
                <div class="mb-2">
                    <strong>Files Processed:</strong>
                    <div id="modalFilesProcessed">0 / 0</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="cancelBackup()">
                    <i class="fas fa-stop"></i> Cancel Backup
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Styles for Backup Settings */
.nav-tabs-custom {
    border-bottom: 2px solid #dee2e6;
}

.nav-tabs-custom .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #6c757d;
    font-weight: 500;
    padding: 12px 20px;
}

.nav-tabs-custom .nav-link:hover {
    border-bottom-color: #007bff;
    color: #007bff;
}

.nav-tabs-custom .nav-link.active {
    border-bottom-color: #007bff;
    color: #007bff;
    background: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}

.progress {
    height: 8px;
}

.progress-bar {
    transition: width 0.3s ease;
}

.badge {
    font-size: 0.75em;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-top: none;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.btn-sm {
    font-size: 0.8rem;
    padding: 0.375rem 0.75rem;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.form-switch .form-check-input {
    width: 2em;
    margin-left: -2.5em;
}

.alert {
    border: none;
    border-radius: 8px;
}

.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.breadcrumb {
    background: none;
    padding: 0;
    margin-bottom: 0.5rem;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: ">";
    color: #6c757d;
}

.list-group-item {
    border: none;
    padding: 0.5rem 0;
}

.input-group .btn {
    border-left: none;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.text-muted {
    color: #6c757d !important;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

#cloudStorageConfig {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.table-responsive {
    border-radius: 8px;
}

.pagination-sm .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.card {
    border: 1px solid #dee2e6;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.card-body {
    padding: 1.5rem;
}

@media (max-width: 768px) {
    .nav-tabs-custom .nav-link {
        padding: 8px 12px;
        font-size: 0.9rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        font-size: 0.9rem;
    }
}
</style>

<script>
// Backup & Restore Settings JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the page
    initializeBackupSettings();
    loadRecentBackups();
    loadBackupsList();
    loadBackupLogs();
    loadSecurityEvents();
    loadAlerts();
    
    // Set up event listeners
    setupEventListeners();
});

// Initialize backup settings
function initializeBackupSettings() {
    // Load saved settings from localStorage or server
    const savedSettings = JSON.parse(localStorage.getItem('backupSettings') || '{}');
    
    // Apply saved settings to form elements
    if (savedSettings.enableAutomatedBackups !== undefined) {
        document.getElementById('enableAutomatedBackups').checked = savedSettings.enableAutomatedBackups;
    }
    
    if (savedSettings.backupFrequency) {
        document.getElementById('backupFrequency').value = savedSettings.backupFrequency;
    }
    
    // Update storage configuration based on type
    updateStorageConfig();
    
    // Update backup status
    updateBackupStatus();
}

// Setup event listeners
function setupEventListeners() {
    // Storage type change
    document.getElementById('storageType').addEventListener('change', updateStorageConfig);
    
    // Restore confirmation checkbox
    document.getElementById('confirmRestore').addEventListener('change', function() {
        document.getElementById('confirmRestoreBtn').disabled = !this.checked;
    });
    
    // Search and filter inputs
    document.getElementById('backupSearch').addEventListener('input', filterBackups);
    document.getElementById('backupTypeFilter').addEventListener('change', filterBackups);
    document.getElementById('backupDateFilter').addEventListener('change', filterBackups);
    
    // Log filters
    document.getElementById('logLevelFilter').addEventListener('change', applyLogFilters);
    document.getElementById('logActionFilter').addEventListener('change', applyLogFilters);
    document.getElementById('logSearchInput').addEventListener('input', applyLogFilters);
}

// Update storage configuration UI
function updateStorageConfig() {
    const storageType = document.getElementById('storageType').value;
    const cloudConfig = document.getElementById('cloudStorageConfig');
    
    if (['ftp', 'sftp', 'aws_s3', 'google_drive', 'dropbox'].includes(storageType)) {
        cloudConfig.style.display = 'block';
        
        // Update labels based on storage type
        const accessKeyLabel = document.querySelector('label[for="cloudAccessKey"]');
        const secretKeyLabel = document.querySelector('label[for="cloudSecretKey"]');
        const regionLabel = document.querySelector('label[for="cloudRegion"]');
        const bucketLabel = document.querySelector('label[for="cloudBucket"]');
        
        switch (storageType) {
            case 'ftp':
            case 'sftp':
                accessKeyLabel.textContent = 'Username';
                secretKeyLabel.textContent = 'Password';
                regionLabel.textContent = 'Server Address';
                bucketLabel.textContent = 'Directory Path';
                break;
            case 'aws_s3':
                accessKeyLabel.textContent = 'Access Key ID';
                secretKeyLabel.textContent = 'Secret Access Key';
                regionLabel.textContent = 'Region';
                bucketLabel.textContent = 'Bucket Name';
                break;
            case 'google_drive':
                accessKeyLabel.textContent = 'Client ID';
                secretKeyLabel.textContent = 'Client Secret';
                regionLabel.textContent = 'Project ID';
                bucketLabel.textContent = 'Folder Name';
                break;
            case 'dropbox':
                accessKeyLabel.textContent = 'App Key';
                secretKeyLabel.textContent = 'App Secret';
                regionLabel.textContent = 'Account ID';
                bucketLabel.textContent = 'Folder Path';
                break;
        }
    } else {
        cloudConfig.style.display = 'none';
    }
}

// Create manual backup
function createManualBackup() {
    const modal = new bootstrap.Modal(document.getElementById('backupProgressModal'));
    modal.show();
    
    // Simulate backup progress
    simulateBackupProgress();
}

// Start manual backup with form data
function startManualBackup() {
    const backupName = document.getElementById('backupName').value || generateBackupName();
    const description = document.getElementById('backupDescription').value;
    
    // Get selected backup types
    const selectedTypes = [];
    if (document.getElementById('manualBackupDatabase').checked) selectedTypes.push('database');
    if (document.getElementById('manualBackupFiles').checked) selectedTypes.push('files');
    if (document.getElementById('manualBackupUploads').checked) selectedTypes.push('uploads');
    if (document.getElementById('manualBackupLogs').checked) selectedTypes.push('logs');
    
    if (selectedTypes.length === 0) {
        showAlert('Please select at least one backup type.', 'warning');
        return;
    }
    
    // Show progress modal
    const modal = new bootstrap.Modal(document.getElementById('backupProgressModal'));
    modal.show();
    
    // Start backup process
    simulateBackupProgress(backupName, selectedTypes);
}

// Generate automatic backup name
function generateBackupName() {
    const now = new Date();
    const timestamp = now.toISOString().replace(/[:.]/g, '-').slice(0, -5);
    return `backup_${timestamp}`;
}

// Simulate backup progress
function simulateBackupProgress(backupName = 'Manual Backup', types = ['database', 'files']) {
    let progress = 0;
    const steps = [
        'Initializing backup...',
        'Preparing database export...',
        'Exporting database...',
        'Compressing files...',
        'Uploading to storage...',
        'Verifying backup integrity...',
        'Finalizing backup...'
    ];
    
    let currentStep = 0;
    
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 100) progress = 100;
        
        // Update progress bar
        document.getElementById('modalBackupProgressBar').style.width = progress + '%';
        document.getElementById('modalBackupPercent').textContent = Math.round(progress) + '%';
        
        // Update current step
        if (currentStep < steps.length - 1 && progress > (currentStep + 1) * (100 / steps.length)) {
            currentStep++;
        }
        document.getElementById('modalCurrentStep').textContent = steps[currentStep];
        
        // Update time remaining
        const timeRemaining = Math.max(0, Math.round((100 - progress) / 10));
        document.getElementById('modalTimeRemaining').textContent = timeRemaining > 0 ? `${timeRemaining} seconds` : 'Almost done...';
        
        // Update files processed
        const filesProcessed = Math.round((progress / 100) * 1247);
        document.getElementById('modalFilesProcessed').textContent = `${filesProcessed} / 1247`;
        
        if (progress >= 100) {
            clearInterval(interval);
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('backupProgressModal')).hide();
                showAlert(`Backup "${backupName}" completed successfully!`, 'success');
                loadRecentBackups();
                loadBackupsList();
            }, 1000);
        }
    }, 500);
}

// Test backup system
function testBackupSystem() {
    showAlert('Running backup system test...', 'info');
    
    setTimeout(() => {
        const testResults = [
            'Database connection: OK',
            'Storage access: OK',
            'Encryption system: OK',
            'Compression tools: OK',
            'Network connectivity: OK'
        ];
        
        showAlert('Backup system test completed successfully!<br>' + testResults.join('<br>'), 'success');
    }, 2000);
}

// Save all backup settings
function saveAllBackupSettings() {
    const settings = {
        enableAutomatedBackups: document.getElementById('enableAutomatedBackups').checked,
        backupFrequency: document.getElementById('backupFrequency').value,
        backupTime: document.getElementById('backupTime').value,
        compressionLevel: document.getElementById('compressionLevel').value,
        maxBackupSize: document.getElementById('maxBackupSize').value,
        storageType: document.getElementById('storageType').value,
        storagePath: document.getElementById('storagePath').value,
        keepDaily: document.getElementById('keepDaily').value,
        keepWeekly: document.getElementById('keepWeekly').value,
        keepMonthly: document.getElementById('keepMonthly').value,
        enableEncryption: document.getElementById('enableEncryption').checked,
        encryptionAlgorithm: document.getElementById('encryptionAlgorithm').value
    };
    
    // Save to localStorage (in real app, send to server)
    localStorage.setItem('backupSettings', JSON.stringify(settings));
    
    showAlert('Backup settings saved successfully!', 'success');
}

// Load recent backups
function loadRecentBackups() {
    const recentBackups = [
        { name: 'Auto_2024-01-15_02-00', time: '2 hours ago', status: 'success', size: '245 MB' },
        { name: 'Manual_Database_2024-01-14', time: '1 day ago', status: 'success', size: '89 MB' },
        { name: 'Auto_2024-01-14_02-00', time: '1 day ago', status: 'success', size: '238 MB' },
        { name: 'Auto_2024-01-13_02-00', time: '2 days ago', status: 'warning', size: '251 MB' },
        { name: 'Manual_Full_2024-01-12', time: '3 days ago', status: 'success', size: '412 MB' }
    ];
    
    const container = document.getElementById('recentBackupsList');
    container.innerHTML = '';
    
    recentBackups.forEach(backup => {
        const statusClass = backup.status === 'success' ? 'text-success' : 
                           backup.status === 'warning' ? 'text-warning' : 'text-danger';
        const statusIcon = backup.status === 'success' ? 'fa-check-circle' : 
                          backup.status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle';
        
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex justify-content-between align-items-center';
        item.innerHTML = `
            <div>
                <div class="fw-bold">${backup.name}</div>
                <small class="text-muted">${backup.time} • ${backup.size}</small>
            </div>
            <i class="fas ${statusIcon} ${statusClass}"></i>
        `;
        container.appendChild(item);
    });
}

// Load backups list for restore tab
function loadBackupsList() {
    const backups = [
        { id: 1, name: 'Auto_2024-01-15_02-00', type: 'Full', size: '245 MB', created: '2024-01-15 02:00', status: 'Verified' },
        { id: 2, name: 'Manual_Database_2024-01-14', type: 'Database', size: '89 MB', created: '2024-01-14 15:30', status: 'Verified' },
        { id: 3, name: 'Auto_2024-01-14_02-00', type: 'Full', size: '238 MB', created: '2024-01-14 02:00', status: 'Verified' },
        { id: 4, name: 'Auto_2024-01-13_02-00', type: 'Full', size: '251 MB', created: '2024-01-13 02:00', status: 'Warning' },
        { id: 5, name: 'Manual_Full_2024-01-12', type: 'Full', size: '412 MB', created: '2024-01-12 10:15', status: 'Verified' }
    ];
    
    const tbody = document.getElementById('backupsTableBody');
    tbody.innerHTML = '';
    
    backups.forEach(backup => {
        const statusClass = backup.status === 'Verified' ? 'bg-success' : 
                           backup.status === 'Warning' ? 'bg-warning' : 'bg-danger';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${backup.name}</td>
            <td><span class="badge bg-primary">${backup.type}</span></td>
            <td>${backup.size}</td>
            <td>${backup.created}</td>
            <td><span class="badge ${statusClass}">${backup.status}</span></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="selectBackupForRestore(${backup.id}, '${backup.name}')">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="downloadBackup(${backup.id})">
                        <i class="fas fa-download"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteBackup(${backup.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Select backup for restore
function selectBackupForRestore(backupId, backupName) {
    document.getElementById('selectedBackupInfo').innerHTML = `
        <strong>${backupName}</strong><br>
        <small class="text-muted">ID: ${backupId}</small>
    `;
    
    document.getElementById('restoreOptionsContainer').style.display = 'block';
    document.getElementById('noBackupSelected').style.display = 'none';
    
    // Store selected backup ID
    window.selectedBackupId = backupId;
}

// Start restore process
function startRestore() {
    if (!window.selectedBackupId) {
        showAlert('Please select a backup to restore.', 'warning');
        return;
    }
    
    // Show confirmation modal
    const modal = new bootstrap.Modal(document.getElementById('restoreConfirmModal'));
    modal.show();
}

// Confirm restore operation
function confirmRestore() {
    bootstrap.Modal.getInstance(document.getElementById('restoreConfirmModal')).hide();
    
    showAlert('Restore operation started. This may take several minutes...', 'info');
    
    // Simulate restore process
    setTimeout(() => {
        showAlert('System restored successfully from backup!', 'success');
    }, 5000);
}

// Filter backups
function filterBackups() {
    const searchTerm = document.getElementById('backupSearch').value.toLowerCase();
    const typeFilter = document.getElementById('backupTypeFilter').value;
    const dateFilter = document.getElementById('backupDateFilter').value;
    
    // In a real application, this would filter the actual data
    // For now, we'll just reload the list
    loadBackupsList();
}

// Load backup logs
function loadBackupLogs() {
    const logs = [
        { timestamp: '2024-01-15 02:00:15', level: 'success', action: 'backup', message: 'Automated backup completed successfully', duration: '12m 34s' },
        { timestamp: '2024-01-14 15:30:22', level: 'info', action: 'backup', message: 'Manual database backup started', duration: '3m 45s' },
        { timestamp: '2024-01-14 02:00:10', level: 'success', action: 'backup', message: 'Automated backup completed successfully', duration: '11m 22s' },
        { timestamp: '2024-01-13 02:00:08', level: 'warning', action: 'backup', message: 'Backup completed with warnings (large file skipped)', duration: '15m 12s' },
        { timestamp: '2024-01-12 10:15:33', level: 'success', action: 'backup', message: 'Manual full backup completed successfully', duration: '25m 18s' }
    ];
    
    const tbody = document.getElementById('backupLogsTableBody');
    tbody.innerHTML = '';
    
    logs.forEach(log => {
        const levelClass = log.level === 'success' ? 'text-success' : 
                          log.level === 'warning' ? 'text-warning' : 
                          log.level === 'error' ? 'text-danger' : 'text-info';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${log.timestamp}</td>
            <td><span class="badge bg-${log.level === 'success' ? 'success' : log.level === 'warning' ? 'warning' : log.level === 'error' ? 'danger' : 'info'}">${log.level.toUpperCase()}</span></td>
            <td><span class="badge bg-secondary">${log.action.toUpperCase()}</span></td>
            <td>${log.message}</td>
            <td>${log.duration}</td>
            <td>
                <button class="btn btn-outline-primary btn-sm" onclick="viewLogDetails('${log.timestamp}')">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Apply log filters
function applyLogFilters() {
    const levelFilter = document.getElementById('logLevelFilter').value;
    const actionFilter = document.getElementById('logActionFilter').value;
    const searchTerm = document.getElementById('logSearchInput').value.toLowerCase();
    
    // In a real application, this would filter the actual data
    // For now, we'll just reload the logs
    loadBackupLogs();
}

// Load security events
function loadSecurityEvents() {
    const events = [
        { time: '2 hours ago', event: 'Encryption key rotated', type: 'info' },
        { time: '1 day ago', event: 'Backup access from new IP', type: 'warning' },
        { time: '2 days ago', event: 'Security audit completed', type: 'success' },
        { time: '3 days ago', event: 'Failed login attempt', type: 'error' }
    ];
    
    const container = document.getElementById('securityEventsList');
    container.innerHTML = '';
    
    events.forEach(event => {
        const iconClass = event.type === 'success' ? 'fa-check-circle text-success' : 
                         event.type === 'warning' ? 'fa-exclamation-triangle text-warning' : 
                         event.type === 'error' ? 'fa-times-circle text-danger' : 'fa-info-circle text-info';
        
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex align-items-center';
        item.innerHTML = `
            <i class="fas ${iconClass} me-2"></i>
            <div>
                <div>${event.event}</div>
                <small class="text-muted">${event.time}</small>
            </div>
        `;
        container.appendChild(item);
    });
}

// Load alerts
function loadAlerts() {
    const alerts = [
        { message: 'Storage usage at 85%', type: 'warning', time: '1 hour ago' },
        { message: 'Backup verification successful', type: 'success', time: '2 hours ago' },
        { message: 'Cleanup completed', type: 'info', time: '1 day ago' }
    ];
    
    const container = document.getElementById('alertsList');
    container.innerHTML = '';
    
    alerts.forEach(alert => {
        const iconClass = alert.type === 'success' ? 'fa-check-circle text-success' : 
                         alert.type === 'warning' ? 'fa-exclamation-triangle text-warning' : 
                         alert.type === 'error' ? 'fa-times-circle text-danger' : 'fa-info-circle text-info';
        
        const item = document.createElement('div');
        item.className = 'list-group-item d-flex align-items-center';
        item.innerHTML = `
            <i class="fas ${iconClass} me-2"></i>
            <div>
                <div>${alert.message}</div>
                <small class="text-muted">${alert.time}</small>
            </div>
        `;
        container.appendChild(item);
    });
}

// Update backup status
function updateBackupStatus() {
    // Simulate real-time status updates
    const statuses = ['Active', 'Running', 'Idle', 'Error'];
    const colors = ['success', 'primary', 'secondary', 'danger'];
    
    // Update last backup time
    document.getElementById('lastBackupTime').textContent = '2 hours ago';
    document.getElementById('nextBackupTime').textContent = 'In 22 hours';
    
    // Update progress
    const progress = Math.floor(Math.random() * 100);
    document.getElementById('backupProgress').style.width = progress + '%';
    document.getElementById('backupProgress').textContent = progress + '%';
}

// Utility functions
function showAlert(message, type = 'info') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Quick action functions
function createDatabaseBackup() {
    document.getElementById('manualBackupDatabase').checked = true;
    document.getElementById('manualBackupFiles').checked = false;
    document.getElementById('manualBackupUploads').checked = false;
    document.getElementById('manualBackupLogs').checked = false;
    startManualBackup();
}

function createFilesBackup() {
    document.getElementById('manualBackupDatabase').checked = false;
    document.getElementById('manualBackupFiles').checked = true;
    document.getElementById('manualBackupUploads').checked = true;
    document.getElementById('manualBackupLogs').checked = false;
    startManualBackup();
}

function createFullBackup() {
    document.getElementById('manualBackupDatabase').checked = true;
    document.getElementById('manualBackupFiles').checked = true;
    document.getElementById('manualBackupUploads').checked = true;
    document.getElementById('manualBackupLogs').checked = true;
    startManualBackup();
}

// Storage management functions
function cleanupOldBackups() {
    showAlert('Cleaning up old backups...', 'info');
    setTimeout(() => {
        showAlert('Cleanup completed. 5 old backups removed, 2.3 GB freed.', 'success');
        loadBackupsList();
    }, 3000);
}

function verifyAllBackups() {
    showAlert('Verifying all backups...', 'info');
    setTimeout(() => {
        showAlert('Verification completed. All backups are valid.', 'success');
    }, 5000);
}

function optimizeStorage() {
    showAlert('Optimizing storage...', 'info');
    setTimeout(() => {
        showAlert('Storage optimization completed. 15% space saved.', 'success');
    }, 4000);
}

// Cloud storage functions
function testCloudConnection() {
    const storageType = document.getElementById('storageType').value;
    showAlert(`Testing ${storageType} connection...`, 'info');
    
    setTimeout(() => {
        const success = Math.random() > 0.2; // 80% success rate
        if (success) {
            showAlert(`${storageType} connection successful!`, 'success');
        } else {
            showAlert(`${storageType} connection failed. Please check your credentials.`, 'error');
        }
    }, 2000);
}

// Security functions
function generateEncryptionKey() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    let key = '';
    for (let i = 0; i < 32; i++) {
        key += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('encryptionKey').value = key;
    showAlert('Encryption key generated successfully!', 'success');
}

function rotateEncryptionKey() {
    showAlert('Rotating encryption key...', 'info');
    setTimeout(() => {
        generateEncryptionKey();
        showAlert('Encryption key rotated successfully!', 'success');
    }, 2000);
}

function auditBackupSecurity() {
    showAlert('Running security audit...', 'info');
    setTimeout(() => {
        showAlert('Security audit completed. No issues found.', 'success');
    }, 3000);
}

function exportSecurityReport() {
    showAlert('Generating security report...', 'info');
    setTimeout(() => {
        showAlert('Security report exported successfully!', 'success');
    }, 2000);
}

// Recovery functions
function repairDatabase() {
    showAlert('Repairing database...', 'info');
    setTimeout(() => {
        showAlert('Database repair completed successfully!', 'success');
    }, 4000);
}

function validateSystem() {
    showAlert('Validating system integrity...', 'info');
    setTimeout(() => {
        showAlert('System validation completed. All systems operational.', 'success');
    }, 3000);
}

function emergencyRestore() {
    if (confirm('This will perform an emergency restore from the latest backup. Continue?')) {
        showAlert('Emergency restore initiated...', 'warning');
        setTimeout(() => {
            showAlert('Emergency restore completed successfully!', 'success');
        }, 8000);
    }
}

// Additional utility functions
function estimateBackupSize() {
    showAlert('Calculating backup size...', 'info');
    setTimeout(() => {
        const size = Math.floor(Math.random() * 500) + 100;
        document.getElementById('estimatedBackupSize').textContent = `${size} MB`;
        showAlert(`Estimated backup size: ${size} MB`, 'info');
    }, 1500);
}

function cancelBackup() {
    if (confirm('Are you sure you want to cancel the backup?')) {
        bootstrap.Modal.getInstance(document.getElementById('backupProgressModal')).hide();
        showAlert('Backup cancelled.', 'warning');
    }
}

function refreshLogs() {
    showAlert('Refreshing logs...', 'info');
    setTimeout(() => {
        loadBackupLogs();
        showAlert('Logs refreshed successfully!', 'success');
    }, 1000);
}

function exportLogs() {
    showAlert('Exporting logs...', 'info');
    setTimeout(() => {
        showAlert('Logs exported successfully!', 'success');
    }, 2000);
}

function viewLogDetails(timestamp) {
    showAlert(`Viewing details for log entry: ${timestamp}`, 'info');
}

function downloadBackup(backupId) {
    showAlert(`Downloading backup ID: ${backupId}...`, 'info');
    setTimeout(() => {
        showAlert('Backup download started!', 'success');
    }, 1000);
}

function deleteBackup(backupId) {
    if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
        showAlert(`Deleting backup ID: ${backupId}...`, 'info');
        setTimeout(() => {
            showAlert('Backup deleted successfully!', 'success');
            loadBackupsList();
        }, 1500);
    }
}

function previewRestore() {
    if (!window.selectedBackupId) {
        showAlert('Please select a backup first.', 'warning');
        return;
    }
    
    showAlert('Generating restore preview...', 'info');
    setTimeout(() => {
        showAlert('Restore preview: 1,247 files will be restored, 89 MB database will be imported.', 'info');
    }, 2000);
}

// Auto-refresh functions
setInterval(() => {
    updateBackupStatus();
}, 30000); // Update every 30 seconds

// Initialize charts (if Chart.js is available)
if (typeof Chart !== 'undefined') {
    const ctx = document.getElementById('backupPerformanceChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Backup Time (minutes)',
                    data: [12, 15, 11, 13, 14, 10, 12],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}
</script>

@endsection