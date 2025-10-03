@extends('layouts.app')

@section('title', 'Maintenance Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Maintenance Details</h1>
            <p class="mb-0 text-muted">View and manage maintenance schedule</p>
        </div>
        <div>
            <a href="{{ route('inventory.maintenance.index') }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to Maintenance
            </a>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-cog"></i> Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="editMaintenance()"><i class="fas fa-edit"></i> Edit</a></li>
                    <li><a class="dropdown-item" href="#" onclick="duplicateMaintenance()"><i class="fas fa-copy"></i> Duplicate</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="printMaintenance()"><i class="fas fa-print"></i> Print</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportMaintenance()"><i class="fas fa-download"></i> Export</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="cancelMaintenance()"><i class="fas fa-times"></i> Cancel</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Maintenance Overview -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Maintenance Overview</h6>
                    <div id="maintenanceStatus">
                        <!-- Status badge will be loaded here -->
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Basic Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 40%;">Maintenance ID:</td>
                                    <td id="maintenanceId">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Type:</td>
                                    <td id="maintenanceType">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Priority:</td>
                                    <td id="maintenancePriority">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Scheduled Date:</td>
                                    <td id="scheduledDate">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Estimated Duration:</td>
                                    <td id="estimatedDuration">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Location:</td>
                                    <td id="maintenanceLocation">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Assignment & Cost</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 40%;">Assigned To:</td>
                                    <td id="assignedTo">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Vendor:</td>
                                    <td id="vendor">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Estimated Cost:</td>
                                    <td id="estimatedCost">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Actual Cost:</td>
                                    <td id="actualCost">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Created By:</td>
                                    <td id="createdBy">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Created Date:</td>
                                    <td id="createdDate">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">Description</h6>
                            <p id="maintenanceDescription" class="text-muted">-</p>
                        </div>
                    </div>
                    
                    <div class="row mt-4" id="notesSection" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">Notes</h6>
                            <p id="maintenanceNotes" class="text-muted">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Asset Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <img id="assetImage" src="" alt="Asset Image" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 id="assetName" class="mb-3">-</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Category:</td>
                                            <td id="assetCategory">-</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Barcode:</td>
                                            <td id="assetBarcode">-</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Serial Number:</td>
                                            <td id="assetSerial">-</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Model:</td>
                                            <td id="assetModel">-</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Status:</td>
                                            <td id="assetStatus">-</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Location:</td>
                                            <td id="assetLocation">-</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Purchase Date:</td>
                                            <td id="assetPurchaseDate">-</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Warranty:</td>
                                            <td id="assetWarranty">-</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completion Details (if completed) -->
            <div id="completionDetails" class="card shadow mb-4" style="display: none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Completion Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 40%;">Completed Date:</td>
                                    <td id="completedDate">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Actual Duration:</td>
                                    <td id="actualDuration">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Completed By:</td>
                                    <td id="completedBy">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 40%;">Parts Cost:</td>
                                    <td id="partsCost">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Labor Cost:</td>
                                    <td id="laborCost">-</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Total Cost:</td>
                                    <td id="totalCost">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-3" id="workPerformedSection" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-success mb-3">Work Performed</h6>
                            <p id="workPerformed" class="text-muted">-</p>
                        </div>
                    </div>
                    
                    <div class="row mt-3" id="partsUsedSection" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-success mb-3">Parts Used</h6>
                            <div id="partsUsed">-</div>
                        </div>
                    </div>
                    
                    <div class="row mt-3" id="completionNotesSection" style="display: none;">
                        <div class="col-12">
                            <h6 class="text-success mb-3">Completion Notes</h6>
                            <p id="completionNotes" class="text-muted">-</p>
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
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading timeline...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div id="quickActions">
                        <!-- Actions will be loaded based on maintenance status -->
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <h4 class="text-primary" id="daysScheduled">0</h4>
                                <small class="text-muted">Days Scheduled</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning" id="daysRemaining">0</h4>
                            <small class="text-muted">Days Remaining</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <h4 class="text-info" id="assetMaintenanceCount">0</h4>
                                <small class="text-muted">Asset Maintenance</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success" id="technicianMaintenanceCount">0</h4>
                            <small class="text-muted">Technician Tasks</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Maintenance -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Related Maintenance</h6>
                </div>
                <div class="card-body">
                    <div id="relatedMaintenance">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recurring Information -->
            <div id="recurringInfo" class="card shadow mb-4" style="display: none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recurring Schedule</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted">Frequency:</td>
                            <td id="recurringFrequency">-</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Interval:</td>
                            <td id="recurringInterval">-</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Next Scheduled:</td>
                            <td id="nextScheduled">-</td>
                        </tr>
                        <tr>
                            <td class="text-muted">End Date:</td>
                            <td id="recurringEndDate">-</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complete Maintenance Modal -->
<div class="modal fade" id="completeMaintenanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="completeMaintenanceForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="completion_date" class="form-label">Completion Date *</label>
                            <input type="datetime-local" class="form-control" id="completion_date" name="completion_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="actual_duration" class="form-label">Actual Duration (hours)</label>
                            <input type="number" class="form-control" id="actual_duration" name="actual_duration" step="0.5" min="0">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="actual_parts_cost" class="form-label">Parts Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="actual_parts_cost" name="actual_parts_cost" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="actual_labor_cost" class="form-label">Labor Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="actual_labor_cost" name="actual_labor_cost" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="actual_total_cost" class="form-label">Total Cost</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="actual_total_cost" name="actual_total_cost" step="0.01" min="0" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="completion_status" class="form-label">Completion Status *</label>
                        <select class="form-select" id="completion_status" name="completion_status" required>
                            <option value="completed">Successfully Completed</option>
                            <option value="partially_completed">Partially Completed</option>
                            <option value="failed">Failed/Unsuccessful</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="work_performed" class="form-label">Work Performed *</label>
                        <textarea class="form-control" id="work_performed" name="work_performed" rows="4" required placeholder="Describe the work that was performed during this maintenance"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parts_used" class="form-label">Parts Used</label>
                        <textarea class="form-control" id="parts_used" name="parts_used" rows="3" placeholder="List any parts or materials used (one per line)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="completion_notes" class="form-label">Completion Notes</label>
                        <textarea class="form-control" id="completion_notes" name="completion_notes" rows="3" placeholder="Additional notes about the completion"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="schedule_next" name="schedule_next">
                                <label class="form-check-label" for="schedule_next">
                                    Schedule next maintenance
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="send_completion_notification" name="send_completion_notification" checked>
                                <label class="form-check-label" for="send_completion_notification">
                                    Send completion notification
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitCompletion()">
                    <i class="fas fa-check"></i> Complete Maintenance
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reschedule Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rescheduleForm">
                    <div class="mb-3">
                        <label for="new_scheduled_date" class="form-label">New Scheduled Date *</label>
                        <input type="datetime-local" class="form-control" id="new_scheduled_date" name="new_scheduled_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reschedule_reason" class="form-label">Reason for Rescheduling *</label>
                        <textarea class="form-control" id="reschedule_reason" name="reschedule_reason" rows="3" required placeholder="Explain why this maintenance is being rescheduled"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notify_technician" name="notify_technician" checked>
                        <label class="form-check-label" for="notify_technician">
                            Notify assigned technician
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitReschedule()">
                    <i class="fas fa-calendar-alt"></i> Reschedule
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let maintenanceId = {{ $maintenance->id ?? 'null' }};

$(document).ready(function() {
    if (maintenanceId) {
        loadMaintenanceDetails(maintenanceId);
        loadActivityTimeline(maintenanceId);
        loadRelatedMaintenance(maintenanceId);
        loadMaintenanceStats(maintenanceId);
    }
    
    // Initialize event listeners
    initializeEventListeners();
});

function initializeEventListeners() {
    // Cost calculation for completion form
    $('#actual_parts_cost, #actual_labor_cost').on('input', function() {
        calculateActualTotalCost();
    });
    
    // Set default completion date to now
    const now = new Date();
    $('#completion_date').val(now.toISOString().slice(0, 16));
}

function loadMaintenanceDetails(id) {
    $.get(`/api/maintenance/${id}`)
        .done(function(maintenance) {
            // Basic information
            $('#maintenanceId').text(maintenance.id);
            $('#maintenanceType').text(formatMaintenanceType(maintenance.maintenance_type));
            $('#maintenancePriority').html(getPriorityBadge(maintenance.priority));
            $('#scheduledDate').text(new Date(maintenance.scheduled_date).toLocaleString());
            $('#estimatedDuration').text(maintenance.estimated_duration ? `${maintenance.estimated_duration} hours` : 'Not specified');
            $('#maintenanceLocation').text(maintenance.location || 'Not specified');
            $('#assignedTo').text(maintenance.assigned_technician?.name || 'Not assigned');
            $('#vendor').text(maintenance.vendor?.name || 'Internal');
            $('#estimatedCost').text(maintenance.estimated_cost ? `$${parseFloat(maintenance.estimated_cost).toFixed(2)}` : 'Not specified');
            $('#actualCost').text(maintenance.actual_cost ? `$${parseFloat(maintenance.actual_cost).toFixed(2)}` : 'Not completed');
            $('#createdBy').text(maintenance.created_by?.name || 'System');
            $('#createdDate').text(new Date(maintenance.created_at).toLocaleDateString());
            $('#maintenanceDescription').text(maintenance.description || 'No description provided');
            
            // Status
            $('#maintenanceStatus').html(getStatusBadge(maintenance.status));
            
            // Notes
            if (maintenance.notes) {
                $('#maintenanceNotes').text(maintenance.notes);
                $('#notesSection').show();
            }
            
            // Asset information
            if (maintenance.inventory_item) {
                const asset = maintenance.inventory_item;
                $('#assetName').text(asset.name);
                $('#assetCategory').text(asset.category?.name || 'N/A');
                $('#assetBarcode').text(asset.barcode || 'N/A');
                $('#assetSerial').text(asset.serial_number || 'N/A');
                $('#assetModel').text(asset.model || 'N/A');
                $('#assetStatus').html(getAssetStatusBadge(asset.status));
                $('#assetLocation').text(asset.location || 'N/A');
                $('#assetPurchaseDate').text(asset.purchase_date ? new Date(asset.purchase_date).toLocaleDateString() : 'N/A');
                $('#assetWarranty').text(asset.warranty_expiry ? new Date(asset.warranty_expiry).toLocaleDateString() : 'N/A');
                
                if (asset.image) {
                    $('#assetImage').attr('src', asset.image);
                } else {
                    $('#assetImage').attr('src', '/images/no-image.png');
                }
            }
            
            // Completion details
            if (maintenance.status === 'completed') {
                $('#completedDate').text(maintenance.completed_at ? new Date(maintenance.completed_at).toLocaleString() : 'N/A');
                $('#actualDuration').text(maintenance.actual_duration ? `${maintenance.actual_duration} hours` : 'Not recorded');
                $('#completedBy').text(maintenance.completed_by?.name || 'N/A');
                $('#partsCost').text(maintenance.parts_cost ? `$${parseFloat(maintenance.parts_cost).toFixed(2)}` : '$0.00');
                $('#laborCost').text(maintenance.labor_cost ? `$${parseFloat(maintenance.labor_cost).toFixed(2)}` : '$0.00');
                $('#totalCost').text(maintenance.actual_cost ? `$${parseFloat(maintenance.actual_cost).toFixed(2)}` : '$0.00');
                
                if (maintenance.work_performed) {
                    $('#workPerformed').text(maintenance.work_performed);
                    $('#workPerformedSection').show();
                }
                
                if (maintenance.parts_used) {
                    const parts = maintenance.parts_used.split('\n').filter(part => part.trim());
                    const partsList = parts.map(part => `<li>${part.trim()}</li>`).join('');
                    $('#partsUsed').html(`<ul>${partsList}</ul>`);
                    $('#partsUsedSection').show();
                }
                
                if (maintenance.completion_notes) {
                    $('#completionNotes').text(maintenance.completion_notes);
                    $('#completionNotesSection').show();
                }
                
                $('#completionDetails').show();
            }
            
            // Recurring information
            if (maintenance.recurring) {
                $('#recurringFrequency').text(formatRecurringFrequency(maintenance.recurring_frequency));
                $('#recurringInterval').text(`Every ${maintenance.recurring_interval} ${maintenance.recurring_frequency}`);
                $('#nextScheduled').text(maintenance.next_scheduled ? new Date(maintenance.next_scheduled).toLocaleDateString() : 'Not scheduled');
                $('#recurringEndDate').text(maintenance.recurring_end_date ? new Date(maintenance.recurring_end_date).toLocaleDateString() : 'No end date');
                $('#recurringInfo').show();
            }
            
            // Load quick actions based on status
            loadQuickActions(maintenance);
        })
        .fail(function() {
            showToast('Error loading maintenance details', 'error');
        });
}

function loadActivityTimeline(id) {
    $.get(`/api/maintenance/${id}/timeline`)
        .done(function(activities) {
            const container = $('#activityTimeline');
            container.empty();
            
            if (activities.length === 0) {
                container.html('<p class="text-muted text-center">No activity recorded</p>');
                return;
            }
            
            activities.forEach(activity => {
                const icon = getActivityIcon(activity.type);
                const color = getActivityColor(activity.type);
                
                container.append(`
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-${color} text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas ${icon}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">${activity.title}</h6>
                            <p class="text-muted mb-1">${activity.description}</p>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> ${new Date(activity.created_at).toLocaleString()}
                                ${activity.user ? `by ${activity.user.name}` : ''}
                            </small>
                        </div>
                    </div>
                `);
            });
        })
        .fail(function() {
            $('#activityTimeline').html('<p class="text-muted text-center">Error loading activity timeline</p>');
        });
}

function loadRelatedMaintenance(id) {
    $.get(`/api/maintenance/${id}/related`)
        .done(function(related) {
            const container = $('#relatedMaintenance');
            container.empty();
            
            if (related.length === 0) {
                container.html('<p class="text-muted text-center">No related maintenance found</p>');
                return;
            }
            
            related.forEach(maintenance => {
                const statusBadge = getStatusBadge(maintenance.status);
                const priorityBadge = getPriorityBadge(maintenance.priority);
                
                container.append(`
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <a href="/inventory/maintenance/${maintenance.id}" class="text-decoration-none">
                                        ${maintenance.maintenance_type}
                                    </a>
                                </h6>
                                <small class="text-muted">${maintenance.inventory_item.name}</small>
                            </div>
                            <div class="text-end">
                                ${statusBadge}
                                ${priorityBadge}
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> ${new Date(maintenance.scheduled_date).toLocaleDateString()}
                        </small>
                    </div>
                `);
            });
        })
        .fail(function() {
            $('#relatedMaintenance').html('<p class="text-muted text-center">Error loading related maintenance</p>');
        });
}

function loadMaintenanceStats(id) {
    $.get(`/api/maintenance/${id}/stats`)
        .done(function(stats) {
            $('#daysScheduled').text(stats.days_scheduled || 0);
            $('#daysRemaining').text(stats.days_remaining || 0);
            $('#assetMaintenanceCount').text(stats.asset_maintenance_count || 0);
            $('#technicianMaintenanceCount').text(stats.technician_maintenance_count || 0);
            
            // Color code days remaining
            const daysRemaining = stats.days_remaining || 0;
            const $daysRemainingElement = $('#daysRemaining');
            if (daysRemaining < 0) {
                $daysRemainingElement.removeClass('text-warning').addClass('text-danger');
            } else if (daysRemaining <= 3) {
                $daysRemainingElement.removeClass('text-warning').addClass('text-warning');
            }
        })
        .fail(function() {
            console.error('Error loading maintenance stats');
        });
}

function loadQuickActions(maintenance) {
    const container = $('#quickActions');
    container.empty();
    
    const actions = [];
    
    if (maintenance.status === 'scheduled') {
        actions.push(`
            <button class="btn btn-success btn-sm w-100 mb-2" onclick="showCompleteModal()">
                <i class="fas fa-check"></i> Mark as Complete
            </button>
        `);
        actions.push(`
            <button class="btn btn-warning btn-sm w-100 mb-2" onclick="showRescheduleModal()">
                <i class="fas fa-calendar-alt"></i> Reschedule
            </button>
        `);
        actions.push(`
            <button class="btn btn-info btn-sm w-100 mb-2" onclick="markInProgress()">
                <i class="fas fa-play"></i> Start Maintenance
            </button>
        `);
    }
    
    if (maintenance.status === 'in_progress') {
        actions.push(`
            <button class="btn btn-success btn-sm w-100 mb-2" onclick="showCompleteModal()">
                <i class="fas fa-check"></i> Mark as Complete
            </button>
        `);
        actions.push(`
            <button class="btn btn-warning btn-sm w-100 mb-2" onclick="showRescheduleModal()">
                <i class="fas fa-calendar-alt"></i> Reschedule
            </button>
        `);
    }
    
    if (maintenance.status !== 'cancelled' && maintenance.status !== 'completed') {
        actions.push(`
            <button class="btn btn-danger btn-sm w-100 mb-2" onclick="cancelMaintenance()">
                <i class="fas fa-times"></i> Cancel
            </button>
        `);
    }
    
    // Always available actions
    actions.push(`
        <button class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="sendReminder()">
            <i class="fas fa-bell"></i> Send Reminder
        </button>
    `);
    
    if (actions.length === 0) {
        container.html('<p class="text-muted text-center">No actions available</p>');
    } else {
        container.html(actions.join(''));
    }
}

function showCompleteModal() {
    $('#completeMaintenanceModal').modal('show');
}

function showRescheduleModal() {
    $('#rescheduleModal').modal('show');
}

function submitCompletion() {
    const formData = new FormData($('#completeMaintenanceForm')[0]);
    
    $.ajax({
        url: `/api/maintenance/${maintenanceId}/complete`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            showToast('Maintenance completed successfully', 'success');
            $('#completeMaintenanceModal').modal('hide');
            
            // Reload page to show updated information
            setTimeout(() => {
                location.reload();
            }, 1500);
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const input = $(`[name="${key}"]`);
                    input.addClass('is-invalid');
                    
                    const errorDiv = input.next('.invalid-feedback');
                    if (errorDiv.length) {
                        errorDiv.text(errors[key][0]);
                    } else {
                        input.after(`<div class="invalid-feedback">${errors[key][0]}</div>`);
                    }
                });
                showToast('Please correct the form errors', 'error');
            } else {
                showToast(xhr.responseJSON?.message || 'Error completing maintenance', 'error');
            }
        }
    });
}

function submitReschedule() {
    const formData = new FormData($('#rescheduleForm')[0]);
    
    $.ajax({
        url: `/api/maintenance/${maintenanceId}/reschedule`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            showToast('Maintenance rescheduled successfully', 'success');
            $('#rescheduleModal').modal('hide');
            
            // Reload page to show updated information
            setTimeout(() => {
                location.reload();
            }, 1500);
        },
        error: function(xhr) {
            showToast(xhr.responseJSON?.message || 'Error rescheduling maintenance', 'error');
        }
    });
}

function markInProgress() {
    if (confirm('Mark this maintenance as in progress?')) {
        $.ajax({
            url: `/api/maintenance/${maintenanceId}/start`,
            method: 'POST',
            success: function(response) {
                showToast('Maintenance marked as in progress', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Error starting maintenance', 'error');
            }
        });
    }
}

function cancelMaintenance() {
    const reason = prompt('Please provide a reason for cancelling this maintenance:');
    if (reason) {
        $.ajax({
            url: `/api/maintenance/${maintenanceId}/cancel`,
            method: 'POST',
            data: { reason: reason },
            success: function(response) {
                showToast('Maintenance cancelled successfully', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Error cancelling maintenance', 'error');
            }
        });
    }
}

function sendReminder() {
    $.ajax({
        url: `/api/maintenance/${maintenanceId}/reminder`,
        method: 'POST',
        success: function(response) {
            showToast('Reminder sent successfully', 'success');
        },
        error: function(xhr) {
            showToast(xhr.responseJSON?.message || 'Error sending reminder', 'error');
        }
    });
}

function editMaintenance() {
    window.location.href = `/inventory/maintenance/${maintenanceId}/edit`;
}

function duplicateMaintenance() {
    if (confirm('Create a duplicate of this maintenance schedule?')) {
        $.ajax({
            url: `/api/maintenance/${maintenanceId}/duplicate`,
            method: 'POST',
            success: function(response) {
                showToast('Maintenance duplicated successfully', 'success');
                setTimeout(() => {
                    window.location.href = `/inventory/maintenance/${response.id}`;
                }, 1500);
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || 'Error duplicating maintenance', 'error');
            }
        });
    }
}

function printMaintenance() {
    window.open(`/inventory/maintenance/${maintenanceId}/print`, '_blank');
}

function exportMaintenance() {
    window.location.href = `/inventory/maintenance/${maintenanceId}/export`;
}

function calculateActualTotalCost() {
    const partsCost = parseFloat($('#actual_parts_cost').val()) || 0;
    const laborCost = parseFloat($('#actual_labor_cost').val()) || 0;
    const totalCost = partsCost + laborCost;
    $('#actual_total_cost').val(totalCost.toFixed(2));
}

// Utility functions
function getStatusBadge(status) {
    const badges = {
        'scheduled': '<span class="badge bg-primary">Scheduled</span>',
        'in_progress': '<span class="badge bg-warning">In Progress</span>',
        'completed': '<span class="badge bg-success">Completed</span>',
        'cancelled': '<span class="badge bg-secondary">Cancelled</span>',
        'overdue': '<span class="badge bg-danger">Overdue</span>',
        'draft': '<span class="badge bg-secondary">Draft</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge bg-secondary">Low</span>',
        'medium': '<span class="badge bg-primary">Medium</span>',
        'high': '<span class="badge bg-warning">High</span>',
        'critical': '<span class="badge bg-danger">Critical</span>'
    };
    return badges[priority] || '<span class="badge bg-secondary">Unknown</span>';
}

function getAssetStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">Active</span>',
        'inactive': '<span class="badge bg-secondary">Inactive</span>',
        'maintenance': '<span class="badge bg-warning">Under Maintenance</span>',
        'retired': '<span class="badge bg-dark">Retired</span>',
        'lost': '<span class="badge bg-danger">Lost</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function formatMaintenanceType(type) {
    const types = {
        'preventive': 'Preventive Maintenance',
        'corrective': 'Corrective Maintenance',
        'emergency': 'Emergency Repair',
        'inspection': 'Inspection'
    };
    return types[type] || type;
}

function formatRecurringFrequency(frequency) {
    const frequencies = {
        'weekly': 'Weekly',
        'monthly': 'Monthly',
        'quarterly': 'Quarterly',
        'semi_annually': 'Semi-Annually',
        'annually': 'Annually'
    };
    return frequencies[frequency] || frequency;
}

function getActivityIcon(type) {
    const icons = {
        'created': 'fa-plus',
        'updated': 'fa-edit',
        'started': 'fa-play',
        'completed': 'fa-check',
        'cancelled': 'fa-times',
        'rescheduled': 'fa-calendar-alt',
        'reminder_sent': 'fa-bell'
    };
    return icons[type] || 'fa-info';
}

function getActivityColor(type) {
    const colors = {
        'created': 'primary',
        'updated': 'info',
        'started': 'warning',
        'completed': 'success',
        'cancelled': 'danger',
        'rescheduled': 'warning',
        'reminder_sent': 'info'
    };
    return colors[type] || 'secondary';
}

function showToast(message, type = 'info') {
    // Implement your toast notification system here
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>
@endsection