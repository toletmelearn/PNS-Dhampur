@extends('layouts.app')

@section('title', 'Document Expiry Alerts')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        Document Expiry Alerts
                    </h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" id="processAlertsBtn">
                            <i class="fas fa-paper-plane"></i> Process Alerts
                        </button>
                        <button type="button" class="btn btn-info" id="refreshAlertsBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-secondary" id="serviceStatusBtn">
                            <i class="fas fa-cog"></i> Service Status
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Alert Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Critical (Expired)</span>
                                    <span class="info-box-number" id="criticalCount">{{ $statistics['critical'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Warning (≤7 days)</span>
                                    <span class="info-box-number" id="warningCount">{{ $statistics['warning'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-info-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Notice (≤30 days)</span>
                                    <span class="info-box-number" id="noticeCount">{{ $statistics['notice'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Documents</span>
                                    <span class="info-box-number" id="totalCount">{{ $statistics['total'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Indicator -->
                    <div id="progressContainer" class="mb-3" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%">
                                <span id="progressText">Processing...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Tabs -->
                    <ul class="nav nav-tabs" id="alertTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="critical-tab" data-bs-toggle="tab" 
                                    data-bs-target="#critical" type="button" role="tab">
                                <i class="fas fa-exclamation-circle text-danger"></i>
                                Critical ({{ count($alerts['critical'] ?? []) }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="warning-tab" data-bs-toggle="tab" 
                                    data-bs-target="#warning" type="button" role="tab">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                Warning ({{ count($alerts['warning'] ?? []) }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notice-tab" data-bs-toggle="tab" 
                                    data-bs-target="#notice" type="button" role="tab">
                                <i class="fas fa-info-circle text-info"></i>
                                Notice ({{ count($alerts['notice'] ?? []) }})
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="alertTabContent">
                        <!-- Critical Alerts -->
                        <div class="tab-pane fade show active" id="critical" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Teacher</th>
                                            <th>Document Type</th>
                                            <th>Document Name</th>
                                            <th>Expiry Date</th>
                                            <th>Days Overdue</th>
                                            <th>Urgency</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="criticalTableBody">
                                        @foreach($alerts['critical'] ?? [] as $alert)
                                        <tr>
                                            <td>
                                                <strong>{{ $alert['teacher_name'] }}</strong><br>
                                                <small class="text-muted">{{ $alert['teacher_email'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $alert['document_type'] }}</span>
                                            </td>
                                            <td>{{ $alert['document_name'] }}</td>
                                            <td>
                                                <span class="text-danger">
                                                    <i class="fas fa-calendar-times"></i>
                                                    {{ \Carbon\Carbon::parse($alert['expiry_date'])->format('M d, Y') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">{{ abs($alert['days_until_expiry']) }} days</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-danger" style="width: {{ min($alert['urgency_score'], 100) }}%">
                                                        {{ $alert['urgency_score'] }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('teacher-documents.show', $alert['document_id']) }}" 
                                                       class="btn btn-outline-primary" title="View Document">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-warning notify-teacher-btn" 
                                                            data-teacher-id="{{ $alert['teacher_id'] }}" title="Notify Teacher">
                                                        <i class="fas fa-bell"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Warning Alerts -->
                        <div class="tab-pane fade" id="warning" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Teacher</th>
                                            <th>Document Type</th>
                                            <th>Document Name</th>
                                            <th>Expiry Date</th>
                                            <th>Days Remaining</th>
                                            <th>Urgency</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="warningTableBody">
                                        @foreach($alerts['warning'] ?? [] as $alert)
                                        <tr>
                                            <td>
                                                <strong>{{ $alert['teacher_name'] }}</strong><br>
                                                <small class="text-muted">{{ $alert['teacher_email'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $alert['document_type'] }}</span>
                                            </td>
                                            <td>{{ $alert['document_name'] }}</td>
                                            <td>
                                                <span class="text-warning">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    {{ \Carbon\Carbon::parse($alert['expiry_date'])->format('M d, Y') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">{{ $alert['days_until_expiry'] }} days</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-warning" style="width: {{ min($alert['urgency_score'], 100) }}%">
                                                        {{ $alert['urgency_score'] }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('teacher-documents.show', $alert['document_id']) }}" 
                                                       class="btn btn-outline-primary" title="View Document">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-warning notify-teacher-btn" 
                                                            data-teacher-id="{{ $alert['teacher_id'] }}" title="Notify Teacher">
                                                        <i class="fas fa-bell"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Notice Alerts -->
                        <div class="tab-pane fade" id="notice" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Teacher</th>
                                            <th>Document Type</th>
                                            <th>Document Name</th>
                                            <th>Expiry Date</th>
                                            <th>Days Remaining</th>
                                            <th>Urgency</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="noticeTableBody">
                                        @foreach($alerts['notice'] ?? [] as $alert)
                                        <tr>
                                            <td>
                                                <strong>{{ $alert['teacher_name'] }}</strong><br>
                                                <small class="text-muted">{{ $alert['teacher_email'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $alert['document_type'] }}</span>
                                            </td>
                                            <td>{{ $alert['document_name'] }}</td>
                                            <td>
                                                <span class="text-info">
                                                    <i class="fas fa-calendar"></i>
                                                    {{ \Carbon\Carbon::parse($alert['expiry_date'])->format('M d, Y') }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $alert['days_until_expiry'] }} days</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-info" style="width: {{ min($alert['urgency_score'], 100) }}%">
                                                        {{ $alert['urgency_score'] }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('teacher-documents.show', $alert['document_id']) }}" 
                                                       class="btn btn-outline-primary" title="View Document">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-info notify-teacher-btn" 
                                                            data-teacher-id="{{ $alert['teacher_id'] }}" title="Notify Teacher">
                                                        <i class="fas fa-bell"></i>
                                                    </button>
                                                </div>
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
    </div>
</div>

<!-- Service Status Modal -->
<div class="modal fade" id="serviceStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog"></i> Document Expiry Alert Service Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="serviceStatusContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Checking service status...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Process Alerts
    $('#processAlertsBtn').click(function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $('#progressContainer').show();
        
        // Simulate progress
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += 10;
            $('.progress-bar').css('width', progress + '%');
            $('#progressText').text(`Processing alerts... ${progress}%`);
            
            if (progress >= 90) {
                clearInterval(progressInterval);
            }
        }, 200);
        
        $.ajax({
            url: '{{ route("teacher-documents.expiry-alerts.process") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                clearInterval(progressInterval);
                $('.progress-bar').css('width', '100%');
                $('#progressText').text('Processing complete!');
                
                setTimeout(() => {
                    $('#progressContainer').hide();
                    $('.progress-bar').css('width', '0%');
                }, 1000);
                
                if (response.success) {
                    toastr.success(`Alerts processed successfully! ${response.processed_count} alerts processed, ${response.notifications_sent} notifications sent.`);
                    refreshAlerts();
                } else {
                    toastr.error(response.message || 'Failed to process alerts');
                }
            },
            error: function(xhr) {
                clearInterval(progressInterval);
                $('#progressContainer').hide();
                $('.progress-bar').css('width', '0%');
                
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Failed to process alerts');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Refresh Alerts
    $('#refreshAlertsBtn').click(function() {
        refreshAlerts();
    });
    
    // Service Status
    $('#serviceStatusBtn').click(function() {
        $('#serviceStatusModal').modal('show');
        checkServiceStatus();
    });
    
    // Notify Teacher
    $('.notify-teacher-btn').click(function() {
        const teacherId = $(this).data('teacher-id');
        const btn = $(this);
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: `{{ route("teacher-documents.expiry-alerts.teacher", ":id") }}`.replace(':id', teacherId),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    toastr.success('Teacher notification sent successfully!');
                } else {
                    toastr.error(response.message || 'Failed to send notification');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Failed to send notification');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    function refreshAlerts() {
        const btn = $('#refreshAlertsBtn');
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
        
        $.ajax({
            url: '{{ route("teacher-documents.expiry-alerts.data") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateStatistics(response.statistics);
                    updateAlertTables(response.alerts);
                    toastr.success('Alerts refreshed successfully!');
                } else {
                    toastr.error(response.message || 'Failed to refresh alerts');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                toastr.error(response?.message || 'Failed to refresh alerts');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    function checkServiceStatus() {
        $('#serviceStatusContent').html(`
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Checking service status...</p>
            </div>
        `);
        
        $.ajax({
            url: '{{ route("teacher-documents.expiry-alerts.service-check") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const status = response.service_status;
                    const statusClass = status.available ? 'success' : 'danger';
                    const statusIcon = status.available ? 'check-circle' : 'times-circle';
                    
                    $('#serviceStatusContent').html(`
                        <div class="alert alert-${statusClass}">
                            <h6><i class="fas fa-${statusIcon}"></i> Service Status: ${status.available ? 'Available' : 'Unavailable'}</h6>
                            <p><strong>Mode:</strong> ${status.mode}</p>
                            <p><strong>Last Check:</strong> ${status.last_check}</p>
                            ${status.message ? `<p><strong>Message:</strong> ${status.message}</p>` : ''}
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Configuration:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Alert Days:</strong> ${status.config.alert_days.join(', ')}</li>
                                    <li><strong>Batch Size:</strong> ${status.config.batch_size}</li>
                                    <li><strong>Max Retries:</strong> ${status.config.max_retries}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Statistics:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Total Documents:</strong> ${status.stats.total_documents}</li>
                                    <li><strong>Expiring Soon:</strong> ${status.stats.expiring_soon}</li>
                                    <li><strong>Expired:</strong> ${status.stats.expired}</li>
                                </ul>
                            </div>
                        </div>
                    `);
                } else {
                    $('#serviceStatusContent').html(`
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-times-circle"></i> Service Check Failed</h6>
                            <p>${response.message}</p>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                $('#serviceStatusContent').html(`
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-times-circle"></i> Service Check Failed</h6>
                        <p>${response?.message || 'Unable to check service status'}</p>
                    </div>
                `);
            }
        });
    }
    
    function updateStatistics(stats) {
        $('#criticalCount').text(stats.critical || 0);
        $('#warningCount').text(stats.warning || 0);
        $('#noticeCount').text(stats.notice || 0);
        $('#totalCount').text(stats.total || 0);
    }
    
    function updateAlertTables(alerts) {
        // Update tab counts
        $('#critical-tab').html(`<i class="fas fa-exclamation-circle text-danger"></i> Critical (${alerts.critical?.length || 0})`);
        $('#warning-tab').html(`<i class="fas fa-exclamation-triangle text-warning"></i> Warning (${alerts.warning?.length || 0})`);
        $('#notice-tab').html(`<i class="fas fa-info-circle text-info"></i> Notice (${alerts.notice?.length || 0})`);
        
        // Update table bodies would require more complex logic
        // For now, we'll just reload the page
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
});
</script>
@endsection