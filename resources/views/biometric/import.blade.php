@extends('layouts.app')

@section('title', 'Biometric Data Import')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Biometric Data Import</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('biometric.index') }}">Biometric</a></li>
                        <li class="breadcrumb-item active">Import</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Status Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-primary rounded-circle">
                                    <i class="mdi mdi-database-import"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="totalImported">{{ $stats['total_imported'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Total Imported</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-success rounded-circle">
                                    <i class="mdi mdi-check-circle"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="successfulImports">{{ $stats['successful'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Successful</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-warning rounded-circle">
                                    <i class="mdi mdi-alert-circle"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="failedImports">{{ $stats['failed'] ?? 0 }}</h5>
                            <p class="text-muted mb-0">Failed</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <span class="avatar-title bg-info rounded-circle">
                                    <i class="mdi mdi-clock-outline"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1" id="lastImport">{{ $stats['last_import'] ?? 'Never' }}</h5>
                            <p class="text-muted mb-0">Last Import</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Methods -->
    <div class="row">
        <div class="col-lg-8">
            <!-- File Upload Import -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-file-upload me-2"></i>
                        File Upload Import
                    </h5>
                </div>
                <div class="card-body">
                    <form id="fileImportForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="import_type" class="form-label">Import Type</label>
                                    <select class="form-select" id="import_type" name="import_type" required>
                                        <option value="">Select Import Type</option>
                                        <option value="attendance">Attendance Records</option>
                                        <option value="enrollment">Biometric Enrollment</option>
                                        <option value="templates">Biometric Templates</option>
                                        <option value="users">User Data</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="file_format" class="form-label">File Format</label>
                                    <select class="form-select" id="file_format" name="file_format" required>
                                        <option value="">Select Format</option>
                                        <option value="csv">CSV</option>
                                        <option value="excel">Excel (XLSX)</option>
                                        <option value="txt">Text File</option>
                                        <option value="dat">DAT File</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="import_file" class="form-label">Select File</label>
                            <input type="file" class="form-control" id="import_file" name="import_file" 
                                   accept=".csv,.xlsx,.txt,.dat" required>
                            <div class="form-text">
                                Supported formats: CSV, Excel, TXT, DAT. Maximum file size: 10MB
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="device_id" class="form-label">Source Device (Optional)</label>
                                    <select class="form-select" id="device_id" name="device_id">
                                        <option value="">Select Device</option>
                                        @foreach($devices as $device)
                                            <option value="{{ $device->id }}">{{ $device->name }} ({{ $device->ip_address }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date_range" class="form-label">Date Range (Optional)</label>
                                    <input type="text" class="form-control" id="date_range" name="date_range" 
                                           placeholder="Select date range">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skip_duplicates" name="skip_duplicates" checked>
                                <label class="form-check-label" for="skip_duplicates">
                                    Skip duplicate records
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="validate_data" name="validate_data" checked>
                                <label class="form-check-label" for="validate_data">
                                    Validate data before import
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="send_notifications" name="send_notifications">
                                <label class="form-check-label" for="send_notifications">
                                    Send completion notifications
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-info" onclick="downloadTemplate()">
                                <i class="mdi mdi-download me-1"></i>Download Template
                            </button>
                            <div>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="previewFile()">
                                    <i class="mdi mdi-eye me-1"></i>Preview
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-upload me-1"></i>Start Import
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Device Sync Import -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-sync me-2"></i>
                        Device Synchronization
                    </h5>
                </div>
                <div class="card-body">
                    <form id="deviceSyncForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sync_device_id" class="form-label">Select Device</label>
                                    <select class="form-select" id="sync_device_id" name="device_id" required>
                                        <option value="">Select Device</option>
                                        @foreach($devices as $device)
                                            <option value="{{ $device->id }}" data-status="{{ $device->status }}">
                                                {{ $device->name }} ({{ $device->ip_address }})
                                                <span class="badge bg-{{ $device->status == 'online' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($device->status) }}
                                                </span>
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sync_type" class="form-label">Sync Type</label>
                                    <select class="form-select" id="sync_type" name="sync_type" required>
                                        <option value="">Select Sync Type</option>
                                        <option value="attendance">Attendance Records</option>
                                        <option value="users">User Data</option>
                                        <option value="templates">Biometric Templates</option>
                                        <option value="all">All Data</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sync_date_from" class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="sync_date_from" name="date_from" 
                                           value="{{ date('Y-m-d', strtotime('-7 days')) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sync_date_to" class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="sync_date_to" name="date_to" 
                                           value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-outline-info me-2" onclick="testConnection()">
                                    <i class="mdi mdi-wifi me-1"></i>Test Connection
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="getDeviceInfo()">
                                    <i class="mdi mdi-information me-1"></i>Device Info
                                </button>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="mdi mdi-sync me-1"></i>Start Sync
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Import Progress & History -->
        <div class="col-lg-4">
            <!-- Current Import Progress -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-progress-clock me-2"></i>
                        Import Progress
                    </h5>
                </div>
                <div class="card-body">
                    <div id="importProgress" style="display: none;">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-sm">Processing...</span>
                                <span class="text-sm" id="progressPercent">0%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">
                                <span id="processedCount">0</span> of <span id="totalCount">0</span> records processed
                            </small>
                        </div>
                        <div class="mb-2">
                            <small class="text-success">
                                <i class="mdi mdi-check me-1"></i>
                                Success: <span id="successCount">0</span>
                            </small>
                        </div>
                        <div class="mb-2">
                            <small class="text-danger">
                                <i class="mdi mdi-close me-1"></i>
                                Failed: <span id="failedCount">0</span>
                            </small>
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelImport()">
                                <i class="mdi mdi-stop me-1"></i>Cancel Import
                            </button>
                        </div>
                    </div>
                    <div id="noImportProgress" class="text-center text-muted">
                        <i class="mdi mdi-information-outline display-4"></i>
                        <p class="mt-2">No import in progress</p>
                    </div>
                </div>
            </div>

            <!-- Recent Imports -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-history me-2"></i>
                        Recent Imports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline-alt">
                        @forelse($recentImports as $import)
                        <div class="timeline-item">
                            <div class="timeline-point timeline-point-{{ $import->status == 'completed' ? 'success' : ($import->status == 'failed' ? 'danger' : 'warning') }}">
                                <i class="mdi mdi-{{ $import->status == 'completed' ? 'check' : ($import->status == 'failed' ? 'close' : 'clock') }}"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">{{ ucfirst($import->type) }} Import</h6>
                                <p class="text-muted mb-1">
                                    {{ $import->total_records }} records
                                    @if($import->status == 'completed')
                                        ({{ $import->successful_records }} successful, {{ $import->failed_records }} failed)
                                    @endif
                                </p>
                                <small class="text-muted">{{ $import->created_at->diffForHumans() }}</small>
                                @if($import->status == 'failed' && $import->error_message)
                                <div class="mt-1">
                                    <small class="text-danger">{{ $import->error_message }}</small>
                                </div>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted">
                            <i class="mdi mdi-history display-4"></i>
                            <p class="mt-2">No recent imports</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Import Guidelines -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-help-circle me-2"></i>
                        Import Guidelines
                    </h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="guidelinesAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fileFormats">
                                    File Formats
                                </button>
                            </h2>
                            <div id="fileFormats" class="accordion-collapse collapse" data-bs-parent="#guidelinesAccordion">
                                <div class="accordion-body">
                                    <ul class="list-unstyled mb-0">
                                        <li><strong>CSV:</strong> Comma-separated values</li>
                                        <li><strong>Excel:</strong> .xlsx format only</li>
                                        <li><strong>TXT:</strong> Tab or comma delimited</li>
                                        <li><strong>DAT:</strong> Device-specific format</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dataRequirements">
                                    Data Requirements
                                </button>
                            </h2>
                            <div id="dataRequirements" class="accordion-collapse collapse" data-bs-parent="#guidelinesAccordion">
                                <div class="accordion-body">
                                    <ul class="list-unstyled mb-0">
                                        <li>• Employee ID is required</li>
                                        <li>• Date format: YYYY-MM-DD</li>
                                        <li>• Time format: HH:MM:SS</li>
                                        <li>• No empty rows allowed</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#bestPractices">
                                    Best Practices
                                </button>
                            </h2>
                            <div id="bestPractices" class="accordion-collapse collapse" data-bs-parent="#guidelinesAccordion">
                                <div class="accordion-body">
                                    <ul class="list-unstyled mb-0">
                                        <li>• Test with small files first</li>
                                        <li>• Backup data before import</li>
                                        <li>• Validate data format</li>
                                        <li>• Check for duplicates</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- File Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">File Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewModalBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="proceedWithImport()">
                    Proceed with Import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Device Info Modal -->
<div class="modal fade" id="deviceInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Device Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="deviceInfoModalBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let importInterval;

$(document).ready(function() {
    // Initialize date range picker
    $('#date_range').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD'
        }
    });
    
    // File import form submission
    $('#fileImportForm').on('submit', function(e) {
        e.preventDefault();
        startFileImport();
    });
    
    // Device sync form submission
    $('#deviceSyncForm').on('submit', function(e) {
        e.preventDefault();
        startDeviceSync();
    });
    
    // Check for ongoing imports
    checkImportStatus();
});

function startFileImport() {
    const formData = new FormData($('#fileImportForm')[0]);
    
    $.ajax({
        url: '{{ route("biometric.import.file") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                showImportProgress();
                startProgressTracking(response.import_id);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            toastr.error(response?.message || 'Import failed to start');
        }
    });
}

function startDeviceSync() {
    const formData = $('#deviceSyncForm').serialize();
    
    $.ajax({
        url: '{{ route("biometric.import.sync") }}',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                showImportProgress();
                startProgressTracking(response.import_id);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            toastr.error(response?.message || 'Sync failed to start');
        }
    });
}

function showImportProgress() {
    $('#noImportProgress').hide();
    $('#importProgress').show();
}

function hideImportProgress() {
    $('#importProgress').hide();
    $('#noImportProgress').show();
}

function startProgressTracking(importId) {
    importInterval = setInterval(function() {
        checkImportProgress(importId);
    }, 2000);
}

function checkImportProgress(importId) {
    $.ajax({
        url: `/biometric/import/${importId}/progress`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateProgressDisplay(response.data);
                
                if (response.data.status === 'completed' || response.data.status === 'failed') {
                    clearInterval(importInterval);
                    setTimeout(function() {
                        hideImportProgress();
                        location.reload();
                    }, 3000);
                }
            }
        },
        error: function() {
            clearInterval(importInterval);
            hideImportProgress();
        }
    });
}

function updateProgressDisplay(data) {
    const percentage = data.total_records > 0 ? Math.round((data.processed_records / data.total_records) * 100) : 0;
    
    $('#progressPercent').text(percentage + '%');
    $('#progressBar').css('width', percentage + '%');
    $('#processedCount').text(data.processed_records);
    $('#totalCount').text(data.total_records);
    $('#successCount').text(data.successful_records);
    $('#failedCount').text(data.failed_records);
}

function checkImportStatus() {
    $.ajax({
        url: '{{ route("biometric.import.status") }}',
        method: 'GET',
        success: function(response) {
            if (response.success && response.data && response.data.status === 'processing') {
                showImportProgress();
                startProgressTracking(response.data.id);
            }
        }
    });
}

function cancelImport() {
    if (confirm('Are you sure you want to cancel the current import?')) {
        $.ajax({
            url: '{{ route("biometric.import.cancel") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    clearInterval(importInterval);
                    hideImportProgress();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Failed to cancel import');
            }
        });
    }
}

function previewFile() {
    const fileInput = $('#import_file')[0];
    const importType = $('#import_type').val();
    const fileFormat = $('#file_format').val();
    
    if (!fileInput.files[0]) {
        toastr.warning('Please select a file first');
        return;
    }
    
    if (!importType || !fileFormat) {
        toastr.warning('Please select import type and file format');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('import_type', importType);
    formData.append('file_format', fileFormat);
    formData.append('_token', '{{ csrf_token() }}');
    
    $.ajax({
        url: '{{ route("biometric.import.preview") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#previewModalBody').html(response.html);
                $('#previewModal').modal('show');
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            toastr.error(response?.message || 'Preview failed');
        }
    });
}

function proceedWithImport() {
    $('#previewModal').modal('hide');
    startFileImport();
}

function downloadTemplate() {
    const importType = $('#import_type').val();
    const fileFormat = $('#file_format').val();
    
    if (!importType || !fileFormat) {
        toastr.warning('Please select import type and file format first');
        return;
    }
    
    window.location.href = `{{ route('biometric.import.template') }}?type=${importType}&format=${fileFormat}`;
}

function testConnection() {
    const deviceId = $('#sync_device_id').val();
    
    if (!deviceId) {
        toastr.warning('Please select a device first');
        return;
    }
    
    $.ajax({
        url: '{{ route("biometric.device.test") }}',
        method: 'POST',
        data: {
            device_id: deviceId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            toastr.error(response?.message || 'Connection test failed');
        }
    });
}

function getDeviceInfo() {
    const deviceId = $('#sync_device_id').val();
    
    if (!deviceId) {
        toastr.warning('Please select a device first');
        return;
    }
    
    $.ajax({
        url: `/biometric/device/${deviceId}/info`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#deviceInfoModalBody').html(response.html);
                $('#deviceInfoModal').modal('show');
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            toastr.error(response?.message || 'Failed to get device info');
        }
    });
}
</script>
@endpush