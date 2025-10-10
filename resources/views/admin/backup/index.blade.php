@extends('layouts.admin')

@section('title', 'Backup Management')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-shield-alt text-primary me-2"></i>
                Backup Management
            </h1>
            <p class="text-muted mb-0">Manage automated backups and system recovery</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" onclick="createBackup()">
                <i class="fas fa-plus me-1"></i>
                Create Backup
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="refreshBackupList()">
                <i class="fas fa-sync-alt me-1"></i>
                Refresh
            </button>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Backups
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-backups">
                                {{ $statistics['total_backups'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Size
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-size">
                                {{ $statistics['total_size_formatted'] ?? '0 B' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hdd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Latest Backup
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="latest-backup">
                                @if(isset($statistics['latest_backup']))
                                    {{ $statistics['latest_backup']->diffForHumans() }}
                                @else
                                    Never
                                @endif
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Backup Status
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="backup-status">
                                <span class="badge badge-success">Healthy</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <button type="button" class="btn btn-outline-primary btn-block" onclick="createBackup('database')">
                                <i class="fas fa-database me-2"></i>
                                Database Backup
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="button" class="btn btn-outline-success btn-block" onclick="createBackup('files')">
                                <i class="fas fa-folder me-2"></i>
                                Files Backup
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="button" class="btn btn-outline-info btn-block" onclick="createBackup('full')">
                                <i class="fas fa-server me-2"></i>
                                Full System Backup
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="button" class="btn btn-outline-warning btn-block" onclick="cleanupBackups()">
                                <i class="fas fa-broom me-2"></i>
                                Cleanup Old Backups
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Configuration -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog me-2"></i>
                        Backup Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <form id="backup-config-form">
                        <div class="form-group">
                            <label for="backup-type">Default Backup Type</label>
                            <select class="form-control" id="backup-type" name="backup_type">
                                <option value="full">Full System Backup</option>
                                <option value="database">Database Only</option>
                                <option value="files">Files Only</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="retention-days">Retention Period (Days)</label>
                            <input type="number" class="form-control" id="retention-days" name="retention_days" value="30" min="1" max="365">
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enable-encryption" name="enable_encryption" checked>
                                <label class="form-check-label" for="enable-encryption">
                                    Enable Encryption
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enable-compression" name="enable_compression" checked>
                                <label class="form-check-label" for="enable-compression">
                                    Enable Compression
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="storage-destinations">Storage Destinations</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="storage-local" name="storage[]" value="local" checked>
                                <label class="form-check-label" for="storage-local">Local Storage</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="storage-s3" name="storage[]" value="s3">
                                <label class="form-check-label" for="storage-s3">Amazon S3</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="storage-ftp" name="storage[]" value="ftp">
                                <label class="form-check-label" for="storage-ftp">FTP Server</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Save Configuration
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Backup Schedule
                    </h6>
                </div>
                <div class="card-body">
                    <div class="schedule-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Daily Full Backup</h6>
                                <small class="text-muted">Every day at 2:00 AM</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="daily-backup" checked>
                                <label class="form-check-label" for="daily-backup"></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="schedule-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Hourly Database Backup</h6>
                                <small class="text-muted">Business hours (8 AM - 6 PM)</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="hourly-backup" checked>
                                <label class="form-check-label" for="hourly-backup"></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="schedule-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Weekly Cloud Backup</h6>
                                <small class="text-muted">Sundays at 3:00 AM</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="weekly-backup" checked>
                                <label class="form-check-label" for="weekly-backup"></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="schedule-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Monthly Archive</h6>
                                <small class="text-muted">1st of every month at 4:00 AM</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="monthly-backup" checked>
                                <label class="form-check-label" for="monthly-backup"></label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="updateSchedule()">
                        <i class="fas fa-sync-alt me-1"></i>
                        Update Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup History -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-history me-2"></i>
                Backup History
            </h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Actions:</div>
                    <a class="dropdown-item" href="#" onclick="exportBackupHistory()">Export History</a>
                    <a class="dropdown-item" href="#" onclick="cleanupBackups()">Cleanup Old Backups</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="backup-history-table" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Duration</th>
                            <th>Storage</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="backup-history-body">
                        <!-- Backup history will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1" role="dialog" aria-labelledby="createBackupModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createBackupModalLabel">
                    <i class="fas fa-plus me-2"></i>
                    Create New Backup
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="create-backup-form">
                    <div class="form-group">
                        <label for="modal-backup-type">Backup Type</label>
                        <select class="form-control" id="modal-backup-type" name="type" required>
                            <option value="full">Full System Backup</option>
                            <option value="database">Database Only</option>
                            <option value="files">Files Only</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modal-encrypt" name="encrypt" checked>
                            <label class="form-check-label" for="modal-encrypt">
                                Encrypt backup
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modal-compress" name="compress" checked>
                            <label class="form-check-label" for="modal-compress">
                                Compress backup
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modal-verify" name="verify" checked>
                            <label class="form-check-label" for="modal-verify">
                                Verify backup integrity
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Storage Destinations</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modal-storage-local" name="storage[]" value="local" checked>
                            <label class="form-check-label" for="modal-storage-local">Local Storage</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modal-storage-s3" name="storage[]" value="s3">
                            <label class="form-check-label" for="modal-storage-s3">Amazon S3</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="modal-storage-ftp" name="storage[]" value="ftp">
                            <label class="form-check-label" for="modal-storage-ftp">FTP Server</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitBackupCreation()">
                    <i class="fas fa-play me-1"></i>
                    Start Backup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="progressModalLabel">
                    <i class="fas fa-cog fa-spin me-2"></i>
                    Creating Backup...
                </h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="backup-progress"></div>
                </div>
                <div id="backup-status-text">Initializing backup process...</div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#backup-history-table').DataTable({
        order: [[0, 'desc']],
        pageLength: 10,
        responsive: true
    });
    
    // Load backup history
    loadBackupHistory();
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        refreshBackupList();
    }, 30000);
});

function createBackup(type = null) {
    if (type) {
        $('#modal-backup-type').val(type);
    }
    $('#createBackupModal').modal('show');
}

function submitBackupCreation() {
    const formData = new FormData($('#create-backup-form')[0]);
    const data = {};
    
    // Convert form data to object
    for (let [key, value] of formData.entries()) {
        if (key === 'storage[]') {
            if (!data.storage) data.storage = [];
            data.storage.push(value);
        } else {
            data[key] = value;
        }
    }
    
    // Add boolean values
    data.encrypt = $('#modal-encrypt').is(':checked');
    data.compress = $('#modal-compress').is(':checked');
    data.verify = $('#modal-verify').is(':checked');
    
    $('#createBackupModal').modal('hide');
    $('#progressModal').modal('show');
    
    // Start backup process
    $.ajax({
        url: '{{ route("admin.backup.create") }}',
        method: 'POST',
        data: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                updateProgress(100, 'Backup completed successfully!');
                setTimeout(function() {
                    $('#progressModal').modal('hide');
                    showAlert('success', 'Backup created successfully!');
                    refreshBackupList();
                }, 2000);
            } else {
                updateProgress(0, 'Backup failed: ' + response.message);
                setTimeout(function() {
                    $('#progressModal').modal('hide');
                    showAlert('error', 'Backup failed: ' + response.message);
                }, 2000);
            }
        },
        error: function(xhr) {
            updateProgress(0, 'Backup failed: ' + xhr.responseText);
            setTimeout(function() {
                $('#progressModal').modal('hide');
                showAlert('error', 'Backup failed. Please try again.');
            }, 2000);
        }
    });
    
    // Simulate progress updates
    let progress = 0;
    const progressInterval = setInterval(function() {
        progress += Math.random() * 20;
        if (progress > 90) progress = 90;
        updateProgress(progress, getProgressMessage(progress));
    }, 1000);
    
    // Clear interval after 30 seconds
    setTimeout(function() {
        clearInterval(progressInterval);
    }, 30000);
}

function updateProgress(percent, message) {
    $('#backup-progress').css('width', percent + '%');
    $('#backup-status-text').text(message);
}

function getProgressMessage(progress) {
    if (progress < 20) return 'Initializing backup process...';
    if (progress < 40) return 'Creating database backup...';
    if (progress < 60) return 'Creating file system backup...';
    if (progress < 80) return 'Compressing and encrypting...';
    if (progress < 95) return 'Uploading to storage destinations...';
    return 'Finalizing backup...';
}

function refreshBackupList() {
    loadBackupHistory();
    updateStatistics();
}

function loadBackupHistory() {
    $.ajax({
        url: '{{ route("admin.backup.history") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateBackupHistoryTable(response.data);
            }
        },
        error: function(xhr) {
            console.error('Failed to load backup history:', xhr);
        }
    });
}

function updateBackupHistoryTable(backups) {
    const tbody = $('#backup-history-body');
    tbody.empty();
    
    backups.forEach(function(backup) {
        const row = `
            <tr>
                <td>${formatDate(backup.created_at)}</td>
                <td>
                    <span class="badge badge-${getTypeColor(backup.type)}">${backup.type}</span>
                </td>
                <td>${backup.size_formatted}</td>
                <td>${backup.duration}s</td>
                <td>${backup.storage_destinations.join(', ')}</td>
                <td>
                    <span class="badge badge-${getStatusColor(backup.status)}">${backup.status}</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary btn-sm" onclick="downloadBackup('${backup.id}')" title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="viewBackupDetails('${backup.id}')" title="Details">
                            <i class="fas fa-info-circle"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="deleteBackup('${backup.id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function updateStatistics() {
    $.ajax({
        url: '{{ route("admin.backup.statistics") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#total-backups').text(response.data.total_backups);
                $('#total-size').text(response.data.total_size_formatted);
                $('#latest-backup').text(response.data.latest_backup_formatted);
            }
        }
    });
}

function cleanupBackups() {
    if (confirm('Are you sure you want to cleanup old backups? This action cannot be undone.')) {
        $.ajax({
            url: '{{ route("admin.backup.cleanup") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', `Cleanup completed! ${response.deleted_count} old backups removed.`);
                    refreshBackupList();
                } else {
                    showAlert('error', 'Cleanup failed: ' + response.message);
                }
            },
            error: function(xhr) {
                showAlert('error', 'Cleanup failed. Please try again.');
            }
        });
    }
}

function downloadBackup(backupId) {
    window.location.href = `{{ route("admin.backup.download", ":id") }}`.replace(':id', backupId);
}

function deleteBackup(backupId) {
    if (confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
        $.ajax({
            url: `{{ route("admin.backup.delete", ":id") }}`.replace(':id', backupId),
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Backup deleted successfully!');
                    refreshBackupList();
                } else {
                    showAlert('error', 'Delete failed: ' + response.message);
                }
            },
            error: function(xhr) {
                showAlert('error', 'Delete failed. Please try again.');
            }
        });
    }
}

function getTypeColor(type) {
    switch (type) {
        case 'full': return 'primary';
        case 'database': return 'info';
        case 'files': return 'success';
        default: return 'secondary';
    }
}

function getStatusColor(status) {
    switch (status) {
        case 'completed': return 'success';
        case 'failed': return 'danger';
        case 'running': return 'warning';
        default: return 'secondary';
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    $('.container-fluid').prepend(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection