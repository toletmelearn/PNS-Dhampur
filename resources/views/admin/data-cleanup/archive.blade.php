@extends('layouts.admin')

@section('title', 'Archive & Purge Data')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-archive mr-2"></i>
                        Archive & Purge Old Data
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.data-cleanup.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-check"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-ban"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Archive Summary -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $archivable['students'] ?? 0 }}</h3>
                                    <p>Old Students</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $archivable['attendance'] ?? 0 }}</h3>
                                    <p>Old Attendance</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $archivable['fees'] ?? 0 }}</h3>
                                    <p>Old Fee Records</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $archivable['logs'] ?? 0 }}</h3>
                                    <p>Old Log Entries</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Archive Configuration -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cog mr-2"></i>
                                        Archive Configuration
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form id="archiveConfigForm">
                                        <div class="form-group">
                                            <label>Archive Students Older Than:</label>
                                            <select class="form-control" name="student_years" id="student_years">
                                                <option value="2">2 Years</option>
                                                <option value="3" selected>3 Years</option>
                                                <option value="5">5 Years</option>
                                                <option value="7">7 Years</option>
                                            </select>
                                            <small class="text-muted">Students who graduated or left more than this period ago</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Archive Attendance Older Than:</label>
                                            <select class="form-control" name="attendance_years" id="attendance_years">
                                                <option value="1">1 Year</option>
                                                <option value="2" selected>2 Years</option>
                                                <option value="3">3 Years</option>
                                                <option value="5">5 Years</option>
                                            </select>
                                            <small class="text-muted">Daily attendance records older than this period</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Archive Fee Records Older Than:</label>
                                            <select class="form-control" name="fee_years" id="fee_years">
                                                <option value="3">3 Years</option>
                                                <option value="5" selected>5 Years</option>
                                                <option value="7">7 Years</option>
                                                <option value="10">10 Years</option>
                                            </select>
                                            <small class="text-muted">Fee payment records older than this period</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Archive Log Entries Older Than:</label>
                                            <select class="form-control" name="log_months" id="log_months">
                                                <option value="3">3 Months</option>
                                                <option value="6" selected>6 Months</option>
                                                <option value="12">1 Year</option>
                                                <option value="24">2 Years</option>
                                            </select>
                                            <small class="text-muted">System logs and audit trails older than this period</small>
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="create_backup" name="create_backup" checked>
                                            <label class="form-check-label" for="create_backup">
                                                Create backup before archiving
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="compress_archive" name="compress_archive" checked>
                                            <label class="form-check-label" for="compress_archive">
                                                Compress archived data
                                            </label>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-play mr-2"></i>
                                        Archive Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6>Preview Archive Impact</h6>
                                        <p class="text-muted">See what data will be archived with current settings</p>
                                        <button type="button" class="btn btn-info btn-block" onclick="previewArchive()">
                                            <i class="fas fa-eye mr-1"></i>
                                            Preview Archive
                                        </button>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Start Archive Process</h6>
                                        <p class="text-muted">Move old data to archive tables</p>
                                        <button type="button" class="btn btn-warning btn-block" onclick="startArchive()">
                                            <i class="fas fa-archive mr-1"></i>
                                            Start Archive
                                        </button>
                                    </div>

                                    <div class="mb-3">
                                        <h6>Purge Archived Data</h6>
                                        <p class="text-muted text-danger">Permanently delete archived data (cannot be undone)</p>
                                        <button type="button" class="btn btn-danger btn-block" onclick="purgeArchive()">
                                            <i class="fas fa-trash mr-1"></i>
                                            Purge Archive
                                        </button>
                                    </div>

                                    <div>
                                        <h6>Download Archive</h6>
                                        <p class="text-muted">Export archived data for external storage</p>
                                        <button type="button" class="btn btn-secondary btn-block" onclick="downloadArchive()">
                                            <i class="fas fa-download mr-1"></i>
                                            Download Archive
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Archive History -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history mr-2"></i>
                                        Archive History
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if(count($archive_history ?? []) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Type</th>
                                                        <th>Records Processed</th>
                                                        <th>Size</th>
                                                        <th>Status</th>
                                                        <th>Duration</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($archive_history as $history)
                                                        <tr>
                                                            <td>{{ $history->created_at->format('Y-m-d H:i') }}</td>
                                                            <td>
                                                                <span class="badge badge-primary">{{ ucfirst($history->type) }}</span>
                                                            </td>
                                                            <td>{{ number_format($history->records_count) }}</td>
                                                            <td>{{ $history->size_mb ? $history->size_mb . ' MB' : 'N/A' }}</td>
                                                            <td>
                                                                <span class="badge badge-{{ $history->status === 'completed' ? 'success' : ($history->status === 'failed' ? 'danger' : 'warning') }}">
                                                                    {{ ucfirst($history->status) }}
                                                                </span>
                                                            </td>
                                                            <td>{{ $history->duration_seconds ? $history->duration_seconds . 's' : 'N/A' }}</td>
                                                            <td>
                                                                @if($history->file_path && file_exists($history->file_path))
                                                                    <a href="{{ route('admin.data-cleanup.download-archive', $history->id) }}" 
                                                                       class="btn btn-sm btn-success">
                                                                        <i class="fas fa-download"></i>
                                                                    </a>
                                                                @endif
                                                                <button type="button" class="btn btn-sm btn-info" 
                                                                        onclick="viewArchiveDetails({{ $history->id }})">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            No archive history found. Start your first archive process above.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Archive Preview Modal -->
<div class="modal fade" id="archivePreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Archive Preview</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="archivePreviewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="confirmArchive()">
                    <i class="fas fa-archive mr-1"></i>
                    Proceed with Archive
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Archive Progress Modal -->
<div class="modal fade" id="archiveProgressModal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Archive in Progress</h5>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-archive fa-3x text-warning mb-3"></i>
                    <h5>Archiving Data...</h5>
                    <p class="text-muted">Please wait while we archive your data. This may take several minutes.</p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%" id="archiveProgress">
                        </div>
                    </div>
                    <div class="mt-2">
                        <small id="archiveStatus">Initializing...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewArchive() {
    const config = getArchiveConfig();
    
    $('#archivePreviewContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading preview...</div>');
    $('#archivePreviewModal').modal('show');
    
    fetch('{{ route("admin.data-cleanup.preview-archive") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(config)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = '<div class="row">';
            
            Object.keys(data.preview).forEach(type => {
                const info = data.preview[type];
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">${type.charAt(0).toUpperCase() + type.slice(1)}</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Records to archive:</strong> ${info.count.toLocaleString()}</p>
                                <p><strong>Estimated size:</strong> ${info.size}</p>
                                <p><strong>Date range:</strong> ${info.date_range}</p>
                                <p><strong>Oldest record:</strong> ${info.oldest}</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            html += `<div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Total records to archive:</strong> ${data.total_records.toLocaleString()}<br>
                <strong>Estimated total size:</strong> ${data.total_size}
            </div>`;
            
            $('#archivePreviewContent').html(html);
        } else {
            $('#archivePreviewContent').html('<div class="alert alert-danger">Error loading preview: ' + data.message + '</div>');
        }
    })
    .catch(error => {
        $('#archivePreviewContent').html('<div class="alert alert-danger">Error loading preview.</div>');
    });
}

function startArchive() {
    if (confirm('Are you sure you want to start the archive process? This will move old data to archive tables.')) {
        const config = getArchiveConfig();
        
        $('#archiveProgressModal').modal('show');
        
        fetch('{{ route("admin.data-cleanup.start-archive") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(config)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Start polling for progress
                pollArchiveProgress(data.job_id);
            } else {
                $('#archiveProgressModal').modal('hide');
                alert('Error starting archive: ' + data.message);
            }
        })
        .catch(error => {
            $('#archiveProgressModal').modal('hide');
            alert('Error starting archive process.');
        });
    }
}

function confirmArchive() {
    $('#archivePreviewModal').modal('hide');
    startArchive();
}

function purgeArchive() {
    const confirmation = prompt('This will permanently delete ALL archived data. Type "PURGE" to confirm:');
    if (confirmation === 'PURGE') {
        if (confirm('Are you absolutely sure? This action cannot be undone!')) {
            fetch('{{ route("admin.data-cleanup.purge-archive") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Archive purged successfully.');
                    location.reload();
                } else {
                    alert('Error purging archive: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error purging archive.');
            });
        }
    }
}

function downloadArchive() {
    window.location.href = '{{ route("admin.data-cleanup.download-archive") }}';
}

function getArchiveConfig() {
    return {
        student_years: document.getElementById('student_years').value,
        attendance_years: document.getElementById('attendance_years').value,
        fee_years: document.getElementById('fee_years').value,
        log_months: document.getElementById('log_months').value,
        create_backup: document.getElementById('create_backup').checked,
        compress_archive: document.getElementById('compress_archive').checked
    };
}

function pollArchiveProgress(jobId) {
    const interval = setInterval(() => {
        fetch(`{{ route("admin.data-cleanup.archive-progress") }}/${jobId}`)
            .then(response => response.json())
            .then(data => {
                if (data.completed) {
                    clearInterval(interval);
                    $('#archiveProgressModal').modal('hide');
                    
                    if (data.success) {
                        alert('Archive completed successfully!');
                        location.reload();
                    } else {
                        alert('Archive failed: ' + data.message);
                    }
                } else {
                    // Update progress
                    const progress = Math.round(data.progress || 0);
                    $('#archiveProgress').css('width', progress + '%');
                    $('#archiveStatus').text(data.status || 'Processing...');
                }
            })
            .catch(error => {
                clearInterval(interval);
                $('#archiveProgressModal').modal('hide');
                alert('Error checking archive progress.');
            });
    }, 2000); // Poll every 2 seconds
}

function viewArchiveDetails(id) {
    // Implement archive details viewing
    alert('Viewing archive details for ID: ' + id);
}
</script>
@endpush