@extends('layouts.app')

@section('title', 'Allocation Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Allocation Details</h1>
            <p class="mb-0 text-muted">View and manage asset allocation information</p>
        </div>
        <div>
            <a href="{{ route('allocations.index') }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to Allocations
            </a>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-cog"></i> Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="editAllocation()"><i class="fas fa-edit"></i> Edit Allocation</a></li>
                    <li><a class="dropdown-item" href="#" onclick="extendAllocation()"><i class="fas fa-calendar-plus"></i> Extend Return Date</a></li>
                    <li><a class="dropdown-item" href="#" onclick="returnAsset()"><i class="fas fa-undo"></i> Return Asset</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="sendReminder()"><i class="fas fa-bell"></i> Send Reminder</a></li>
                    <li><a class="dropdown-item" href="#" onclick="printAllocation()"><i class="fas fa-print"></i> Print Details</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportAllocation()"><i class="fas fa-download"></i> Export</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Allocation Overview -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Allocation Overview</h6>
                    <div id="allocationStatus">
                        <!-- Status badge will be populated dynamically -->
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Asset Information -->
                            <div class="mb-4">
                                <h6 class="text-primary mb-3">Asset Information</h6>
                                <div class="row">
                                    <div class="col-4">
                                        <img id="assetImage" src="" alt="Asset Image" class="img-fluid rounded" style="max-height: 120px;">
                                    </div>
                                    <div class="col-8">
                                        <h5 id="assetName" class="mb-2"></h5>
                                        <p class="text-muted mb-1">
                                            <strong>Category:</strong> <span id="assetCategory"></span>
                                        </p>
                                        <p class="text-muted mb-1">
                                            <strong>Barcode:</strong> <span id="assetBarcode"></span>
                                        </p>
                                        <p class="text-muted mb-1">
                                            <strong>Serial Number:</strong> <span id="assetSerial"></span>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <strong>Model:</strong> <span id="assetModel"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Employee Information -->
                            <div class="mb-4">
                                <h6 class="text-primary mb-3">Allocated To</h6>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                        <i class="fas fa-user fa-2x text-white"></i>
                                    </div>
                                    <div>
                                        <h6 id="employeeName" class="mb-1"></h6>
                                        <p class="text-muted mb-1">
                                            <strong>Email:</strong> <span id="employeeEmail"></span>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <strong>Department:</strong> <span id="employeeDepartment"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Allocation Details -->
                            <div class="mb-4">
                                <h6 class="text-primary mb-3">Allocation Details</h6>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Allocated Date:</strong></div>
                                    <div class="col-sm-7" id="allocatedDate"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Expected Return:</strong></div>
                                    <div class="col-sm-7" id="expectedReturnDate"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Days Used:</strong></div>
                                    <div class="col-sm-7" id="daysUsed"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Purpose:</strong></div>
                                    <div class="col-sm-7" id="purpose"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Location:</strong></div>
                                    <div class="col-sm-7" id="location"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Priority:</strong></div>
                                    <div class="col-sm-7" id="priority"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Condition:</strong></div>
                                    <div class="col-sm-7" id="condition"></div>
                                </div>
                            </div>

                            <!-- Return Information (if returned) -->
                            <div id="returnInfo" class="mb-4" style="display: none;">
                                <h6 class="text-success mb-3">Return Information</h6>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Returned Date:</strong></div>
                                    <div class="col-sm-7" id="returnedDate"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Return Condition:</strong></div>
                                    <div class="col-sm-7" id="returnCondition"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-sm-5"><strong>Returned By:</strong></div>
                                    <div class="col-sm-7" id="returnedBy"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">Notes</h6>
                            <div id="notes" class="bg-light p-3 rounded">
                                <!-- Notes will be populated dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Activity Timeline</h6>
                </div>
                <div class="card-body">
                    <div id="activityTimeline">
                        <!-- Timeline will be populated dynamically -->
                    </div>
                </div>
            </div>

            <!-- Return Notes (if returned) -->
            <div id="returnNotesCard" class="card shadow mb-4" style="display: none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Return Notes</h6>
                </div>
                <div class="card-body">
                    <div id="returnNotes">
                        <!-- Return notes will be populated dynamically -->
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" onclick="returnAsset()" id="returnBtn">
                            <i class="fas fa-undo"></i> Return Asset
                        </button>
                        <button class="btn btn-warning" onclick="extendAllocation()" id="extendBtn">
                            <i class="fas fa-calendar-plus"></i> Extend Return Date
                        </button>
                        <button class="btn btn-info" onclick="sendReminder()" id="reminderBtn">
                            <i class="fas fa-bell"></i> Send Reminder
                        </button>
                        <button class="btn btn-outline-primary" onclick="editAllocation()">
                            <i class="fas fa-edit"></i> Edit Allocation
                        </button>
                        <button class="btn btn-outline-secondary" onclick="printAllocation()">
                            <i class="fas fa-print"></i> Print Details
                        </button>
                    </div>
                </div>
            </div>

            <!-- Allocation Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 id="totalDays" class="text-primary mb-0">-</h4>
                                <small class="text-muted">Total Days</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 id="remainingDays" class="text-warning mb-0">-</h4>
                            <small class="text-muted">Days Remaining</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h6 id="employeeAllocations" class="text-info mb-0">-</h6>
                                <small class="text-muted">Employee's Active</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 id="assetAllocations" class="text-success mb-0">-</h6>
                            <small class="text-muted">Asset History</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Allocations -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Related Allocations</h6>
                </div>
                <div class="card-body">
                    <div id="relatedAllocations">
                        <!-- Related allocations will be populated dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Return Asset Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnModalLabel">Return Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="returnForm">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="return_date" class="form-label">Return Date *</label>
                            <input type="date" class="form-control" id="return_date" name="return_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="return_condition" class="form-label">Condition at Return *</label>
                            <select class="form-select" id="return_condition" name="return_condition" required>
                                <option value="">Select Condition</option>
                                <option value="excellent">Excellent</option>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                                <option value="damaged">Damaged</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="return_notes" class="form-label">Return Notes</label>
                        <textarea class="form-control" id="return_notes" name="return_notes" rows="4" placeholder="Any notes about the asset condition or return process"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="needs_maintenance" name="needs_maintenance">
                        <label class="form-check-label" for="needs_maintenance">
                            Asset needs maintenance
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="send_confirmation" name="send_confirmation" checked>
                        <label class="form-check-label" for="send_confirmation">
                            Send confirmation email to employee
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Return Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Extend Allocation Modal -->
<div class="modal fade" id="extendModal" tabindex="-1" aria-labelledby="extendModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="extendModalLabel">Extend Return Date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="extendForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_return_date" class="form-label">New Expected Return Date *</label>
                        <input type="date" class="form-control" id="new_return_date" name="new_return_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="extension_reason" class="form-label">Reason for Extension *</label>
                        <textarea class="form-control" id="extension_reason" name="extension_reason" rows="3" required placeholder="Please provide a reason for extending the return date"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notify_employee" name="notify_employee" checked>
                        <label class="form-check-label" for="notify_employee">
                            Notify employee about extension
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Extend Date</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let allocationId = {{ $allocation->id ?? 'null' }};

$(document).ready(function() {
    if (allocationId) {
        loadAllocationDetails();
        loadActivityTimeline();
        loadRelatedAllocations();
        loadStatistics();
    }
    
    // Set default return date to today
    $('#return_date').val(new Date().toISOString().split('T')[0]);
    
    // Return form submission
    $('#returnForm').on('submit', function(e) {
        e.preventDefault();
        submitReturn();
    });
    
    // Extend form submission
    $('#extendForm').on('submit', function(e) {
        e.preventDefault();
        submitExtension();
    });
});

function loadAllocationDetails() {
    $.get(`/api/allocations/${allocationId}`)
        .done(function(allocation) {
            // Update allocation status
            const statusBadge = getStatusBadge(allocation.status);
            $('#allocationStatus').html(statusBadge);
            
            // Asset information
            $('#assetName').text(allocation.inventory_item.name);
            $('#assetCategory').text(allocation.inventory_item.category?.name || 'N/A');
            $('#assetBarcode').text(allocation.inventory_item.barcode || 'N/A');
            $('#assetSerial').text(allocation.inventory_item.serial_number || 'N/A');
            $('#assetModel').text(allocation.inventory_item.model_number || 'N/A');
            
            if (allocation.inventory_item.image_url) {
                $('#assetImage').attr('src', allocation.inventory_item.image_url).show();
            } else {
                $('#assetImage').hide();
            }
            
            // Employee information
            $('#employeeName').text(allocation.employee.name);
            $('#employeeEmail').text(allocation.employee.email || 'N/A');
            $('#employeeDepartment').text(allocation.employee.department || 'N/A');
            
            // Allocation details
            $('#allocatedDate').text(new Date(allocation.allocated_date).toLocaleDateString());
            $('#expectedReturnDate').text(allocation.expected_return_date ? new Date(allocation.expected_return_date).toLocaleDateString() : 'Not specified');
            $('#daysUsed').text(allocation.days_used || 0);
            $('#purpose').text(allocation.purpose || 'N/A');
            $('#location').text(allocation.location || 'N/A');
            $('#priority').html(getPriorityBadge(allocation.priority));
            $('#condition').html(getConditionBadge(allocation.condition));
            $('#notes').text(allocation.notes || 'No notes available');
            
            // Return information (if returned)
            if (allocation.status === 'returned' && allocation.returned_date) {
                $('#returnedDate').text(new Date(allocation.returned_date).toLocaleDateString());
                $('#returnCondition').html(getConditionBadge(allocation.return_condition));
                $('#returnedBy').text(allocation.returned_by?.name || 'N/A');
                $('#returnInfo').show();
                
                if (allocation.return_notes) {
                    $('#returnNotes').text(allocation.return_notes);
                    $('#returnNotesCard').show();
                }
                
                // Hide action buttons for returned items
                $('#returnBtn, #extendBtn, #reminderBtn').hide();
            }
            
            // Update button states based on status
            updateActionButtons(allocation.status);
        })
        .fail(function() {
            showToast('Error loading allocation details', 'error');
        });
}

function loadActivityTimeline() {
    $.get(`/api/allocations/${allocationId}/timeline`)
        .done(function(activities) {
            const timeline = $('#activityTimeline');
            timeline.empty();
            
            if (activities.length === 0) {
                timeline.append('<p class="text-muted">No activity recorded</p>');
                return;
            }
            
            activities.forEach((activity, index) => {
                const isLast = index === activities.length - 1;
                const icon = getActivityIcon(activity.type);
                const color = getActivityColor(activity.type);
                
                timeline.append(`
                    <div class="d-flex ${!isLast ? 'mb-3' : ''}">
                        <div class="flex-shrink-0">
                            <div class="bg-${color} rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-${icon} text-white"></i>
                            </div>
                            ${!isLast ? '<div class="bg-light" style="width: 2px; height: 30px; margin-left: 19px; margin-top: 5px;"></div>' : ''}
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">${activity.title}</h6>
                            <p class="text-muted mb-1">${activity.description}</p>
                            <small class="text-muted">
                                ${new Date(activity.created_at).toLocaleString()} 
                                ${activity.user ? `by ${activity.user.name}` : ''}
                            </small>
                        </div>
                    </div>
                `);
            });
        })
        .fail(function() {
            $('#activityTimeline').append('<p class="text-muted">Error loading activity timeline</p>');
        });
}

function loadRelatedAllocations() {
    $.get(`/api/allocations/${allocationId}/related`)
        .done(function(allocations) {
            const container = $('#relatedAllocations');
            container.empty();
            
            if (allocations.length === 0) {
                container.append('<p class="text-muted small">No related allocations</p>');
                return;
            }
            
            allocations.forEach(allocation => {
                const statusBadge = getStatusBadge(allocation.status);
                container.append(`
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div>
                            <div class="fw-bold small">${allocation.inventory_item.name}</div>
                            <div class="text-muted small">${allocation.employee.name}</div>
                        </div>
                        <div class="text-end">
                            ${statusBadge}
                            <div class="text-muted small">${new Date(allocation.allocated_date).toLocaleDateString()}</div>
                        </div>
                    </div>
                `);
            });
        });
}

function loadStatistics() {
    $.get(`/api/allocations/${allocationId}/statistics`)
        .done(function(stats) {
            $('#totalDays').text(stats.total_days || 0);
            $('#remainingDays').text(stats.remaining_days || 0);
            $('#employeeAllocations').text(stats.employee_active_allocations || 0);
            $('#assetAllocations').text(stats.asset_allocation_history || 0);
            
            // Update remaining days color based on value
            const remainingDays = stats.remaining_days || 0;
            const $remainingElement = $('#remainingDays');
            $remainingElement.removeClass('text-warning text-danger text-success');
            
            if (remainingDays < 0) {
                $remainingElement.addClass('text-danger');
            } else if (remainingDays <= 3) {
                $remainingElement.addClass('text-warning');
            } else {
                $remainingElement.addClass('text-success');
            }
        });
}

function returnAsset() {
    $('#returnModal').modal('show');
}

function extendAllocation() {
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    $('#new_return_date').attr('min', today);
    $('#extendModal').modal('show');
}

function sendReminder() {
    $.post(`/api/allocations/${allocationId}/send-reminder`)
        .done(function() {
            showToast('Reminder sent successfully', 'success');
        })
        .fail(function() {
            showToast('Error sending reminder', 'error');
        });
}

function editAllocation() {
    window.location.href = `/inventory/allocations/${allocationId}/edit`;
}

function printAllocation() {
    window.open(`/inventory/allocations/${allocationId}/print`, '_blank');
}

function exportAllocation() {
    window.location.href = `/inventory/allocations/${allocationId}/export`;
}

function submitReturn() {
    const formData = new FormData($('#returnForm')[0]);
    
    $.ajax({
        url: `/api/allocations/${allocationId}/return`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#returnModal').modal('hide');
            showToast('Asset returned successfully', 'success');
            
            // Reload page to show updated information
            setTimeout(() => {
                location.reload();
            }, 1500);
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const input = $(`#returnForm [name="${key}"]`);
                    input.addClass('is-invalid');
                    // Add error display logic here
                });
                showToast('Please correct the form errors', 'error');
            } else {
                showToast(xhr.responseJSON?.message || 'Error returning asset', 'error');
            }
        }
    });
}

function submitExtension() {
    const formData = new FormData($('#extendForm')[0]);
    
    $.ajax({
        url: `/api/allocations/${allocationId}/extend`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#extendModal').modal('hide');
            showToast('Return date extended successfully', 'success');
            
            // Reload page to show updated information
            setTimeout(() => {
                location.reload();
            }, 1500);
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const input = $(`#extendForm [name="${key}"]`);
                    input.addClass('is-invalid');
                });
                showToast('Please correct the form errors', 'error');
            } else {
                showToast(xhr.responseJSON?.message || 'Error extending allocation', 'error');
            }
        }
    });
}

function updateActionButtons(status) {
    if (status === 'returned' || status === 'lost') {
        $('#returnBtn, #extendBtn, #reminderBtn').prop('disabled', true);
    }
}

// Utility functions
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">Active</span>',
        'returned': '<span class="badge bg-info">Returned</span>',
        'overdue': '<span class="badge bg-danger">Overdue</span>',
        'lost': '<span class="badge bg-dark">Lost</span>',
        'damaged': '<span class="badge bg-warning">Damaged</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge bg-secondary">Low</span>',
        'medium': '<span class="badge bg-primary">Medium</span>',
        'high': '<span class="badge bg-warning">High</span>',
        'urgent': '<span class="badge bg-danger">Urgent</span>'
    };
    return badges[priority] || '<span class="badge bg-secondary">Unknown</span>';
}

function getConditionBadge(condition) {
    const badges = {
        'excellent': '<span class="badge bg-success">Excellent</span>',
        'good': '<span class="badge bg-info">Good</span>',
        'fair': '<span class="badge bg-warning">Fair</span>',
        'poor': '<span class="badge bg-danger">Poor</span>',
        'damaged': '<span class="badge bg-dark">Damaged</span>'
    };
    return badges[condition] || '<span class="badge bg-secondary">Unknown</span>';
}

function getActivityIcon(type) {
    const icons = {
        'allocated': 'plus',
        'extended': 'calendar-plus',
        'returned': 'undo',
        'reminder_sent': 'bell',
        'status_changed': 'edit',
        'note_added': 'comment'
    };
    return icons[type] || 'info';
}

function getActivityColor(type) {
    const colors = {
        'allocated': 'success',
        'extended': 'warning',
        'returned': 'info',
        'reminder_sent': 'primary',
        'status_changed': 'secondary',
        'note_added': 'light'
    };
    return colors[type] || 'secondary';
}

function showToast(message, type = 'info') {
    // Implement your toast notification system here
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>
@endsection