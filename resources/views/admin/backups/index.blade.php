@extends('layouts.app')

@section('title', 'Backup Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Backup Management</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Backup Management</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Total Backups">Total Backups</h5>
                            <h3 class="my-2 py-1" id="total-backups">{{ $backupStats['total_backups'] ?? 0 }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="total-backups-chart" data-colors="#0acf97"></div>
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
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Successful Backups">Successful</h5>
                            <h3 class="my-2 py-1 text-success" id="successful-backups">{{ $backupStats['successful_backups'] ?? 0 }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="success-chart" data-colors="#0acf97"></div>
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
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Failed Backups">Failed</h5>
                            <h3 class="my-2 py-1 text-danger" id="failed-backups">{{ $backupStats['failed_backups'] ?? 0 }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="failed-chart" data-colors="#fa5c7c"></div>
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
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Last Database Backup">Last DB Backup</h5>
                            <p class="mb-0 text-muted" id="last-db-backup">
                                @if($backupStats['last_database_backup'])
                                    {{ \Carbon\Carbon::parse($backupStats['last_database_backup']->created_at)->diffForHumans() }}
                                @else
                                    Never
                                @endif
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <i class="mdi mdi-database widget-icon bg-primary-lighten text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Backup Operations</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Database Backup -->
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <h5 class="mb-3"><i class="mdi mdi-database me-2"></i>Database Backup</h5>
                                <p class="text-muted">Create a backup of the entire database including all tables and data.</p>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="db-incremental">
                                        <label class="form-check-label" for="db-incremental">
                                            Incremental backup
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="db-compress" checked>
                                        <label class="form-check-label" for="db-compress">
                                            Compress backup
                                        </label>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="createDatabaseBackup()">
                                    <i class="mdi mdi-backup-restore me-1"></i>Create Database Backup
                                </button>
                            </div>
                        </div>

                        <!-- File Backup -->
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <h5 class="mb-3"><i class="mdi mdi-folder me-2"></i>File Backup</h5>
                                <p class="text-muted">Backup application files, uploads, and storage directories.</p>
                                <div class="mb-3">
                                    <label class="form-label">Directories to backup:</label>
                                    <div class="form-check">
                                        <input class="form-check-input file-dir" type="checkbox" value="storage" id="dir-storage" checked>
                                        <label class="form-check-label" for="dir-storage">Storage</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input file-dir" type="checkbox" value="public" id="dir-public" checked>
                                        <label class="form-check-label" for="dir-public">Public</label>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success" onclick="createFileBackup()">
                                    <i class="mdi mdi-folder-zip me-1"></i>Create File Backup
                                </button>
                            </div>
                        </div>

                        <!-- Data Export -->
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <h5 class="mb-3"><i class="mdi mdi-export me-2"></i>Data Export</h5>
                                <p class="text-muted">Export data in various formats for migration or analysis.</p>
                                <div class="mb-3">
                                    <label class="form-label">Export Format:</label>
                                    <select class="form-select" id="export-format">
                                        <option value="json">JSON</option>
                                        <option value="csv">CSV</option>
                                        <option value="sql">SQL</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-info" onclick="exportData()">
                                    <i class="mdi mdi-download me-1"></i>Export Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Import Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Data Import</h4>
                </div>
                <div class="card-body">
                    <form id="import-form" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Select Backup File</label>
                                    <input type="file" class="form-control" name="backup_file" id="backup-file" accept=".json,.csv,.sql,.zip">
                                    <small class="text-muted">Supported formats: JSON, CSV, SQL, ZIP (Max: 100MB)</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Format</label>
                                    <select class="form-select" name="format" id="import-format">
                                        <option value="auto">Auto Detect</option>
                                        <option value="json">JSON</option>
                                        <option value="csv">CSV</option>
                                        <option value="sql">SQL</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Options</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="truncate" id="import-truncate">
                                        <label class="form-check-label" for="import-truncate">
                                            Truncate tables before import
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="ignore_errors" id="import-ignore-errors">
                                        <label class="form-check-label" for="import-ignore-errors">
                                            Continue on errors
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-warning" onclick="importData()">
                            <i class="mdi mdi-upload me-1"></i>Import Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title">Backup History</h4>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshBackupLogs()">
                        <i class="mdi mdi-refresh me-1"></i>Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="backup-logs-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Duration</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backupLogs as $log)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $log->type === 'database' ? 'primary' : ($log->type === 'files' ? 'success' : 'info') }}">
                                            {{ ucfirst($log->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $log->status === 'success' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $log->filename ?? 'N/A' }}</td>
                                    <td>{{ $log->file_size ? number_format($log->file_size / 1024 / 1024, 2) . ' MB' : 'N/A' }}</td>
                                    <td>{{ $log->duration ? $log->duration . 's' : 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y H:i') }}</td>
                                    <td>
                                        @if($log->status === 'success' && $log->file_path)
                                        <a href="{{ route('admin.backups.download', $log->id) }}" class="btn btn-sm btn-outline-primary" title="Download">
                                            <i class="mdi mdi-download"></i>
                                        </a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteBackup({{ $log->id }})" title="Delete">
                                            <i class="mdi mdi-delete"></i>
                                        </button>
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

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 mb-0">Processing backup operation...</p>
                <p class="text-muted" id="operation-status">Please wait while we complete the operation.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// CSRF Token
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Create Database Backup
function createDatabaseBackup() {
    const incremental = document.getElementById('db-incremental').checked;
    const compress = document.getElementById('db-compress').checked;
    
    showLoadingModal('Creating database backup...');
    
    fetch('{{ route("admin.backups.database.create") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            incremental: incremental,
            compress: compress
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingModal();
        if (data.success) {
            showAlert('success', 'Database backup created successfully!');
            refreshBackupLogs();
            updateStats();
        } else {
            showAlert('error', 'Failed to create database backup: ' + data.message);
        }
    })
    .catch(error => {
        hideLoadingModal();
        showAlert('error', 'An error occurred: ' + error.message);
    });
}

// Create File Backup
function createFileBackup() {
    const directories = Array.from(document.querySelectorAll('.file-dir:checked')).map(cb => cb.value);
    
    showLoadingModal('Creating file backup...');
    
    fetch('{{ route("admin.backups.files.create") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            directories: directories,
            exclude: ['logs', 'cache']
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingModal();
        if (data.success) {
            showAlert('success', 'File backup created successfully!');
            refreshBackupLogs();
            updateStats();
        } else {
            showAlert('error', 'Failed to create file backup: ' + data.message);
        }
    })
    .catch(error => {
        hideLoadingModal();
        showAlert('error', 'An error occurred: ' + error.message);
    });
}

// Export Data
function exportData() {
    const format = document.getElementById('export-format').value;
    
    showLoadingModal('Exporting data...');
    
    fetch('{{ route("admin.backups.export") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            format: format
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingModal();
        if (data.success) {
            showAlert('success', 'Data export completed successfully!');
            refreshBackupLogs();
        } else {
            showAlert('error', 'Failed to export data: ' + data.message);
        }
    })
    .catch(error => {
        hideLoadingModal();
        showAlert('error', 'An error occurred: ' + error.message);
    });
}

// Import Data
function importData() {
    const formData = new FormData(document.getElementById('import-form'));
    
    showLoadingModal('Importing data...');
    
    fetch('{{ route("admin.backups.import") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingModal();
        if (data.success) {
            showAlert('success', 'Data import completed successfully!');
            document.getElementById('import-form').reset();
            refreshBackupLogs();
        } else {
            showAlert('error', 'Failed to import data: ' + data.message);
        }
    })
    .catch(error => {
        hideLoadingModal();
        showAlert('error', 'An error occurred: ' + error.message);
    });
}

// Delete Backup
function deleteBackup(id) {
    if (!confirm('Are you sure you want to delete this backup? This action cannot be undone.')) {
        return;
    }
    
    fetch(`{{ route("admin.backups.index") }}/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Backup deleted successfully!');
            refreshBackupLogs();
            updateStats();
        } else {
            showAlert('error', 'Failed to delete backup: ' + data.message);
        }
    })
    .catch(error => {
        showAlert('error', 'An error occurred: ' + error.message);
    });
}

// Refresh Backup Logs
function refreshBackupLogs() {
    location.reload();
}

// Update Statistics
function updateStats() {
    fetch('{{ route("admin.backups.stats") }}')
    .then(response => response.json())
    .then(data => {
        document.getElementById('total-backups').textContent = data.total_backups;
        document.getElementById('successful-backups').textContent = data.successful_backups;
        document.getElementById('failed-backups').textContent = data.failed_backups;
    })
    .catch(error => {
        console.error('Failed to update stats:', error);
    });
}

// Show Loading Modal
function showLoadingModal(message) {
    document.getElementById('operation-status').textContent = message;
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
}

// Hide Loading Modal
function hideLoadingModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
    if (modal) {
        modal.hide();
    }
}

// Show Alert
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

// Auto-refresh stats every 30 seconds
setInterval(updateStats, 30000);
</script>
@endsection