<!-- Approval Actions Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalTitle">Approval Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approvalForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approvalReason" class="form-label" id="approvalReasonLabel">Reason</label>
                        <textarea class="form-control" id="approvalReason" name="reason" rows="3" placeholder="Enter reason for this action..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="approvalPriority" class="form-label">Priority</label>
                        <select class="form-select" id="approvalPriority" name="priority">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="notifyUser" name="notify_user" checked>
                            <label class="form-check-label" for="notifyUser">
                                Notify user about this action
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="delegateSection" style="display: none;">
                        <label for="delegateTo" class="form-label">Delegate To</label>
                        <select class="form-select" id="delegateTo" name="delegate_to">
                            <option value="">Select user to delegate...</option>
                            <!-- Options will be populated via AJAX -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="delegateBtn" onclick="showDelegateSection()">
                        <i class="fas fa-user-friends"></i> Delegate
                    </button>
                    <button type="submit" class="btn btn-primary" id="approvalSubmitBtn">
                        <i class="fas fa-check"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#approvalForm').on('submit', function(e) {
        e.preventDefault();
        
        const auditId = $(this).data('audit-id');
        const action = $(this).data('action');
        const formData = new FormData(this);
        
        let url = '';
        if (action === 'approve') {
            url = `/class-data-audit/${auditId}/approve`;
        } else if (action === 'reject') {
            url = `/class-data-audit/${auditId}/reject`;
        } else if (action === 'delegate') {
            url = `/class-data-audit/${auditId}/delegate`;
        }
        
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#approvalModal').modal('hide');
                    showAlert(response.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(response.message || 'Action failed', 'error');
                }
            },
            error: function(xhr) {
                let message = 'An error occurred';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert(message, 'error');
            }
        });
    });
});

function showDelegateSection() {
    $('#delegateSection').show();
    $('#approvalForm').data('action', 'delegate');
    $('#approvalModalTitle').text('Delegate Approval');
    $('#approvalReasonLabel').text('Delegation Reason');
    $('#delegateBtn').hide();
    
    // Load users for delegation
    loadDelegationUsers();
}

function loadDelegationUsers() {
    $.get('/class-data-audit/users/approvers')
        .done(function(response) {
            if (response.success) {
                const select = $('#delegateTo');
                select.empty().append('<option value="">Select user to delegate...</option>');
                
                response.data.forEach(user => {
                    select.append(`<option value="${user.id}">${user.name} (${user.email})</option>`);
                });
            }
        })
        .fail(function() {
            showAlert('Failed to load users for delegation', 'error');
        });
}
</script>