<!-- Rollback Confirmation Modal -->
<div class="modal fade" id="rollbackModal" tabindex="-1" aria-labelledby="rollbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="rollbackModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Rollback
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rollbackForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This action will rollback the data to a previous version. This operation cannot be undone automatically.
                    </div>
                    
                    <div id="rollbackVersionInfo">
                        <!-- Version information will be loaded here -->
                    </div>
                    
                    <div class="mb-3">
                        <label for="rollbackReason" class="form-label">Rollback Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rollbackReason" name="reason" rows="3" required placeholder="Please provide a reason for this rollback..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="createBackup" name="create_backup" checked>
                            <label class="form-check-label" for="createBackup">
                                Create backup of current version before rollback
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="notifyStakeholders" name="notify_stakeholders" checked>
                            <label class="form-check-label" for="notifyStakeholders">
                                Notify relevant stakeholders about this rollback
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rollbackPriority" class="form-label">Priority Level</label>
                        <select class="form-select" id="rollbackPriority" name="priority">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="approvalRequiredSection" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            This rollback requires approval due to its risk level or impact. An approval request will be created.
                        </div>
                        
                        <div class="mb-3">
                            <label for="approvalJustification" class="form-label">Approval Justification</label>
                            <textarea class="form-control" id="approvalJustification" name="approval_justification" rows="2" placeholder="Provide justification for approval..."></textarea>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmRollback" required>
                        <label class="form-check-label" for="confirmRollback">
                            <strong>I understand the risks and confirm this rollback operation</strong>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" id="rollbackSubmitBtn">
                        <i class="fas fa-undo"></i> Confirm Rollback
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rollback Progress Modal -->
<div class="modal fade" id="rollbackProgressModal" tabindex="-1" aria-labelledby="rollbackProgressModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rollbackProgressModalLabel">
                    <i class="fas fa-spinner fa-spin"></i> Processing Rollback
                </h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="rollbackProgressBar"></div>
                </div>
                <div id="rollbackProgressStatus">
                    <p class="mb-1">Initializing rollback process...</p>
                    <small class="text-muted">Please do not close this window.</small>
                </div>
                <div id="rollbackProgressSteps" class="mt-3">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center" id="step-validation">
                            <span>Validating rollback request</span>
                            <span class="badge bg-secondary">Pending</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center" id="step-backup">
                            <span>Creating backup of current version</span>
                            <span class="badge bg-secondary">Pending</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center" id="step-rollback">
                            <span>Performing rollback operation</span>
                            <span class="badge bg-secondary">Pending</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center" id="step-verification">
                            <span>Verifying rollback integrity</span>
                            <span class="badge bg-secondary">Pending</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center" id="step-notification">
                            <span>Sending notifications</span>
                            <span class="badge bg-secondary">Pending</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="rollbackProgressFooter" style="display: none;">
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-refresh"></i> Refresh Page
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#rollbackForm').on('submit', function(e) {
        e.preventDefault();
        
        const versionId = $(this).data('version-id');
        const formData = new FormData(this);
        
        // Show progress modal
        $('#rollbackModal').modal('hide');
        $('#rollbackProgressModal').modal('show');
        
        // Start rollback process
        performRollback(versionId, formData);
    });
    
    // Load version info when modal is shown
    $('#rollbackModal').on('show.bs.modal', function() {
        const versionId = $('#rollbackForm').data('version-id');
        if (versionId) {
            loadRollbackVersionInfo(versionId);
        }
    });
    
    // Check if approval is required based on priority
    $('#rollbackPriority').on('change', function() {
        const priority = $(this).val();
        if (priority === 'urgent' || priority === 'critical') {
            $('#approvalRequiredSection').show();
            $('#approvalJustification').prop('required', true);
        } else {
            $('#approvalRequiredSection').hide();
            $('#approvalJustification').prop('required', false);
        }
    });
});

function loadRollbackVersionInfo(versionId) {
    $.get(`/class-data-audit/versions/${versionId}`)
        .done(function(response) {
            if (response.success) {
                const version = response.data;
                const infoHtml = `
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Rollback Target Version</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Version:</strong> <span class="badge bg-info">v${version.version_number}</span></p>
                                    <p><strong>Type:</strong> ${version.version_type}</p>
                                    <p><strong>Created:</strong> ${new Date(version.created_at).toLocaleString()}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Created By:</strong> ${version.created_by_name || 'System'}</p>
                                    <p><strong>Size:</strong> ${formatBytes(version.data_size)}</p>
                                    <p><strong>Checksum:</strong> <code>${version.checksum ? version.checksum.substring(0, 16) + '...' : 'N/A'}</code></p>
                                </div>
                            </div>
                            ${version.changes_summary ? `<p><strong>Summary:</strong> ${version.changes_summary}</p>` : ''}
                        </div>
                    </div>
                `;
                $('#rollbackVersionInfo').html(infoHtml);
            }
        })
        .fail(function() {
            $('#rollbackVersionInfo').html('<div class="alert alert-danger">Failed to load version information</div>');
        });
}

function performRollback(versionId, formData) {
    let currentStep = 0;
    const steps = ['validation', 'backup', 'rollback', 'verification', 'notification'];
    
    function updateProgress(step, status, message) {
        const stepElement = $(`#step-${step}`);
        const badge = stepElement.find('.badge');
        
        switch (status) {
            case 'processing':
                badge.removeClass('bg-secondary bg-success bg-danger').addClass('bg-warning').text('Processing...');
                break;
            case 'completed':
                badge.removeClass('bg-secondary bg-warning bg-danger').addClass('bg-success').text('Completed');
                break;
            case 'failed':
                badge.removeClass('bg-secondary bg-warning bg-success').addClass('bg-danger').text('Failed');
                break;
        }
        
        if (message) {
            $('#rollbackProgressStatus').html(`<p class="mb-1">${message}</p><small class="text-muted">Please do not close this window.</small>`);
        }
        
        // Update progress bar
        const progress = ((currentStep + 1) / steps.length) * 100;
        $('#rollbackProgressBar').css('width', progress + '%');
    }
    
    // Simulate rollback process (replace with actual AJAX call)
    function processStep(stepIndex) {
        if (stepIndex >= steps.length) {
            // Rollback completed
            $('#rollbackProgressStatus').html(`
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong>Rollback completed successfully!</strong>
                    <p class="mb-0 mt-2">The data has been rolled back to the selected version.</p>
                </div>
            `);
            $('#rollbackProgressFooter').show();
            return;
        }
        
        const step = steps[stepIndex];
        currentStep = stepIndex;
        
        updateProgress(step, 'processing', `Processing ${step.replace('-', ' ')}...`);
        
        // Simulate processing time
        setTimeout(() => {
            updateProgress(step, 'completed');
            processStep(stepIndex + 1);
        }, 1000 + Math.random() * 2000);
    }
    
    // Start the rollback process
    $.ajax({
        url: `/class-data-audit/versions/${versionId}/rollback`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                processStep(0);
            } else {
                $('#rollbackProgressStatus').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Rollback failed:</strong> ${response.message || 'Unknown error occurred'}
                    </div>
                `);
                $('#rollbackProgressFooter').show();
            }
        },
        error: function(xhr) {
            let message = 'An error occurred during rollback';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            
            $('#rollbackProgressStatus').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Rollback failed:</strong> ${message}
                </div>
            `);
            $('#rollbackProgressFooter').show();
        }
    });
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>