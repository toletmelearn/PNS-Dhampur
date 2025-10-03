@extends('layouts.app')

@section('title', 'Edit Maintenance Schedule')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.maintenance.index') }}">Maintenance</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.maintenance.show', ':id') }}" id="maintenanceShowLink">View</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">Edit Maintenance Schedule</h1>
            <p class="mb-0 text-muted">Update maintenance task details and scheduling information</p>
        </div>
        <div>
            <a href="#" class="btn btn-outline-secondary" id="backToMaintenanceBtn">
                <i class="fas fa-arrow-left"></i> Back to Details
            </a>
            <a href="{{ route('inventory.maintenance.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-list"></i> All Maintenance
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Form -->
        <div class="col-lg-8">
            <form id="editMaintenanceForm">
                <input type="hidden" id="maintenanceId" name="maintenance_id">
                
                <!-- Asset Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Asset Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <label for="asset_search" class="form-label">Search Asset <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="asset_search" placeholder="Search by name, barcode, or serial number...">
                                    <button class="btn btn-outline-secondary" type="button" onclick="clearAssetSelection()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <input type="hidden" id="selected_asset_id" name="asset_id" required>
                                <div id="asset_search_results" class="list-group mt-2" style="display: none;"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Current Status</label>
                                <div id="current_asset_status" class="form-control-plaintext">
                                    <span class="badge bg-secondary">Not Selected</span>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Asset Details -->
                        <div id="selected_asset_details" class="mt-3" style="display: none;">
                            <div class="row">
                                <div class="col-md-2">
                                    <img id="asset_image" src="" alt="Asset Image" class="img-fluid rounded" style="max-height: 80px;">
                                </div>
                                <div class="col-md-10">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong id="asset_name"></strong><br>
                                            <small class="text-muted">Category: <span id="asset_category"></span></small><br>
                                            <small class="text-muted">Barcode: <span id="asset_barcode"></span></small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Serial: <span id="asset_serial"></span></small><br>
                                            <small class="text-muted">Location: <span id="asset_location"></span></small><br>
                                            <small class="text-muted">Status: <span id="asset_status_display"></span></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Details -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Maintenance Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="maintenance_type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="maintenance_type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="preventive">Preventive Maintenance</option>
                                    <option value="corrective">Corrective Maintenance</option>
                                    <option value="emergency">Emergency Repair</option>
                                    <option value="upgrade">Upgrade/Enhancement</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="scheduled_date" name="scheduled_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="estimated_duration" class="form-label">Estimated Duration (hours)</label>
                                <input type="number" class="form-control" id="estimated_duration" name="estimated_duration" min="0.5" step="0.5" placeholder="e.g., 2.5">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe the maintenance work to be performed..." required></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignment & Resources -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Assignment & Resources</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="assigned_technician" class="form-label">Assigned Technician</label>
                                <select class="form-select" id="assigned_technician" name="assigned_technician">
                                    <option value="">Select Technician</option>
                                    <!-- Technicians will be loaded dynamically -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="external_vendor" class="form-label">External Vendor</label>
                                <select class="form-select" id="external_vendor" name="external_vendor">
                                    <option value="">Select Vendor (if applicable)</option>
                                    <!-- Vendors will be loaded dynamically -->
                                </select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label for="maintenance_location" class="form-label">Maintenance Location</label>
                                <input type="text" class="form-control" id="maintenance_location" name="maintenance_location" placeholder="Where will the maintenance be performed?">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cost Estimation -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Cost Estimation</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="estimated_cost" class="form-label">Estimated Total Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="parts_cost" class="form-label">Parts Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="parts_cost" name="parts_cost" min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="labor_cost" class="form-label">Labor Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="labor_cost" name="labor_cost" min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Total cost will be automatically calculated from parts and labor costs if not specified.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Additional Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label for="safety_requirements" class="form-label">Safety Requirements</label>
                                <textarea class="form-control" id="safety_requirements" name="safety_requirements" rows="2" placeholder="Any special safety requirements or precautions..."></textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional notes or instructions..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recurring Maintenance -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recurring Maintenance</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring">
                            <label class="form-check-label" for="is_recurring">
                                Set up recurring maintenance schedule
                            </label>
                        </div>

                        <div id="recurring_options" style="display: none;">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="recurring_frequency" class="form-label">Frequency</label>
                                    <select class="form-select" id="recurring_frequency" name="recurring_frequency">
                                        <option value="">Select Frequency</option>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="recurring_interval" class="form-label">Interval</label>
                                    <input type="number" class="form-control" id="recurring_interval" name="recurring_interval" min="1" placeholder="e.g., 2 (every 2 months)">
                                </div>
                                <div class="col-md-4">
                                    <label for="recurring_end_date" class="form-label">End Date (Optional)</label>
                                    <input type="date" class="form-control" id="recurring_end_date" name="recurring_end_date">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Notification Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="send_notification" name="send_notification" checked>
                            <label class="form-check-label" for="send_notification">
                                Send notification to assigned technician
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="send_reminders" name="send_reminders">
                            <label class="form-check-label" for="send_reminders">
                                Send reminder notifications
                            </label>
                        </div>

                        <div id="reminder_options" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="reminder_days" class="form-label">Days before maintenance</label>
                                    <input type="number" class="form-control" id="reminder_days" name="reminder_days" min="1" value="1" placeholder="1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo"></i> Reset Changes
                                </button>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" onclick="saveAsDraft()">
                                    <i class="fas fa-save"></i> Save as Draft
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Update Maintenance
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Current Status -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Current Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div id="current_status">
                            <span class="badge bg-secondary">Loading...</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Created</label>
                        <div id="created_info" class="text-muted">Loading...</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Updated</label>
                        <div id="updated_info" class="text-muted">Loading...</div>
                    </div>
                </div>
            </div>

            <!-- Quick Tips -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-lightbulb text-warning"></i>
                            <small>Use preventive maintenance to reduce unexpected breakdowns</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock text-info"></i>
                            <small>Schedule maintenance during low-usage periods</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-user-cog text-success"></i>
                            <small>Assign experienced technicians for complex tasks</small>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-bell text-primary"></i>
                            <small>Enable reminders to ensure timely completion</small>
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-chart-line text-danger"></i>
                            <small>Track costs to optimize maintenance budget</small>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Activity History -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Activity History</h6>
                </div>
                <div class="card-body">
                    <div id="activity_timeline">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading activity history...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Maintenance -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Related Maintenance</h6>
                </div>
                <div class="card-body">
                    <div id="related_maintenance">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading related maintenance...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let maintenanceData = {};
let originalFormData = {};

$(document).ready(function() {
    const maintenanceId = getMaintenanceIdFromUrl();
    if (maintenanceId) {
        $('#maintenanceId').val(maintenanceId);
        loadMaintenanceData(maintenanceId);
        updateBreadcrumbLinks(maintenanceId);
    }
    
    loadTechnicians();
    loadVendors();
    setupEventListeners();
});

function getMaintenanceIdFromUrl() {
    const pathParts = window.location.pathname.split('/');
    const editIndex = pathParts.indexOf('edit');
    if (editIndex > 0) {
        return pathParts[editIndex - 1];
    }
    return null;
}

function updateBreadcrumbLinks(maintenanceId) {
    $('#maintenanceShowLink').attr('href', `/inventory/maintenance/${maintenanceId}`);
    $('#backToMaintenanceBtn').attr('href', `/inventory/maintenance/${maintenanceId}`);
}

function loadMaintenanceData(maintenanceId) {
    $.get(`/api/inventory/maintenance/${maintenanceId}`)
        .done(function(data) {
            maintenanceData = data;
            populateForm(data);
            loadActivityHistory(maintenanceId);
            loadRelatedMaintenance(data.asset_id, maintenanceId);
            
            // Store original form data for reset functionality
            originalFormData = getFormData();
        })
        .fail(function() {
            showToast('Error loading maintenance data', 'error');
        });
}

function populateForm(data) {
    // Asset information
    if (data.asset) {
        selectAsset(data.asset);
    }
    
    // Maintenance details
    $('#maintenance_type').val(data.type);
    $('#priority').val(data.priority);
    $('#scheduled_date').val(formatDateTimeForInput(data.scheduled_date));
    $('#estimated_duration').val(data.estimated_duration);
    $('#description').val(data.description);
    
    // Assignment & Resources
    $('#assigned_technician').val(data.assigned_technician_id);
    $('#external_vendor').val(data.external_vendor_id);
    $('#maintenance_location').val(data.maintenance_location);
    
    // Cost estimation
    $('#estimated_cost').val(data.estimated_cost);
    $('#parts_cost').val(data.parts_cost);
    $('#labor_cost').val(data.labor_cost);
    
    // Additional information
    $('#safety_requirements').val(data.safety_requirements);
    $('#notes').val(data.notes);
    
    // Recurring maintenance
    if (data.is_recurring) {
        $('#is_recurring').prop('checked', true);
        $('#recurring_options').show();
        $('#recurring_frequency').val(data.recurring_frequency);
        $('#recurring_interval').val(data.recurring_interval);
        $('#recurring_end_date').val(data.recurring_end_date);
    }
    
    // Notification settings
    $('#send_notification').prop('checked', data.send_notification);
    $('#send_reminders').prop('checked', data.send_reminders);
    if (data.send_reminders) {
        $('#reminder_options').show();
        $('#reminder_days').val(data.reminder_days);
    }
    
    // Update status information
    updateStatusInfo(data);
}

function selectAsset(asset) {
    $('#selected_asset_id').val(asset.id);
    $('#asset_search').val(asset.name);
    
    // Show asset details
    $('#asset_image').attr('src', asset.image_url || '/images/default-asset.png');
    $('#asset_name').text(asset.name);
    $('#asset_category').text(asset.category);
    $('#asset_barcode').text(asset.barcode || 'N/A');
    $('#asset_serial').text(asset.serial_number || 'N/A');
    $('#asset_location').text(asset.location || 'N/A');
    $('#asset_status_display').html(getAssetStatusBadge(asset.status));
    $('#current_asset_status').html(getAssetStatusBadge(asset.status));
    
    $('#selected_asset_details').show();
    $('#asset_search_results').hide();
}

function updateStatusInfo(data) {
    $('#current_status').html(getMaintenanceStatusBadge(data.status));
    $('#created_info').text(`${data.created_by} on ${formatDate(data.created_at)}`);
    $('#updated_info').text(`${formatDate(data.updated_at)}`);
}

function loadTechnicians() {
    $.get('/api/technicians')
        .done(function(technicians) {
            const select = $('#assigned_technician');
            technicians.forEach(technician => {
                select.append(`<option value="${technician.id}">${technician.name}</option>`);
            });
        });
}

function loadVendors() {
    $.get('/api/vendors')
        .done(function(vendors) {
            const select = $('#external_vendor');
            vendors.forEach(vendor => {
                select.append(`<option value="${vendor.id}">${vendor.name}</option>`);
            });
        });
}

function loadActivityHistory(maintenanceId) {
    $.get(`/api/inventory/maintenance/${maintenanceId}/activity`)
        .done(function(activities) {
            const timeline = $('#activity_timeline');
            timeline.empty();
            
            if (activities.length === 0) {
                timeline.html('<div class="text-muted text-center">No activity history available</div>');
                return;
            }
            
            activities.forEach(activity => {
                const icon = getActivityIcon(activity.type);
                const color = getActivityColor(activity.type);
                
                timeline.append(`
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-${color} text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas ${icon} fa-sm"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-bold">${activity.description}</div>
                            <div class="text-muted small">
                                ${activity.user_name} • ${formatDateTime(activity.created_at)}
                            </div>
                        </div>
                    </div>
                `);
            });
        })
        .fail(function() {
            $('#activity_timeline').html('<div class="text-muted text-center">Error loading activity history</div>');
        });
}

function loadRelatedMaintenance(assetId, currentMaintenanceId) {
    $.get(`/api/inventory/assets/${assetId}/maintenance?exclude=${currentMaintenanceId}`)
        .done(function(maintenance) {
            const container = $('#related_maintenance');
            container.empty();
            
            if (maintenance.length === 0) {
                container.html('<div class="text-muted text-center">No related maintenance found</div>');
                return;
            }
            
            maintenance.slice(0, 5).forEach(item => {
                const statusBadge = getMaintenanceStatusBadge(item.status);
                const typeBadge = getMaintenanceTypeBadge(item.type);
                
                container.append(`
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold">${typeBadge}</div>
                                <div class="text-muted small">${formatDate(item.scheduled_date)}</div>
                            </div>
                            <div>${statusBadge}</div>
                        </div>
                        <div class="text-truncate small mt-1">${item.description}</div>
                    </div>
                `);
            });
            
            if (maintenance.length > 5) {
                container.append(`
                    <div class="text-center">
                        <a href="/inventory/maintenance?asset=${assetId}" class="btn btn-sm btn-outline-primary">
                            View All (${maintenance.length})
                        </a>
                    </div>
                `);
            }
        })
        .fail(function() {
            $('#related_maintenance').html('<div class="text-muted text-center">Error loading related maintenance</div>');
        });
}

function setupEventListeners() {
    // Asset search
    $('#asset_search').on('input', debounce(searchAssets, 300));
    
    // Recurring maintenance toggle
    $('#is_recurring').change(function() {
        if ($(this).is(':checked')) {
            $('#recurring_options').slideDown();
        } else {
            $('#recurring_options').slideUp();
        }
    });
    
    // Reminder notifications toggle
    $('#send_reminders').change(function() {
        if ($(this).is(':checked')) {
            $('#reminder_options').slideDown();
        } else {
            $('#reminder_options').slideUp();
        }
    });
    
    // Cost calculation
    $('#parts_cost, #labor_cost').on('input', calculateTotalCost);
    
    // Form submission
    $('#editMaintenanceForm').submit(function(e) {
        e.preventDefault();
        updateMaintenance();
    });
}

function searchAssets() {
    const query = $('#asset_search').val().trim();
    if (query.length < 2) {
        $('#asset_search_results').hide();
        return;
    }
    
    $.get('/api/inventory/assets/search', { q: query })
        .done(function(assets) {
            const results = $('#asset_search_results');
            results.empty();
            
            if (assets.length === 0) {
                results.append('<div class="list-group-item text-muted">No assets found</div>');
            } else {
                assets.forEach(asset => {
                    const statusBadge = getAssetStatusBadge(asset.status);
                    results.append(`
                        <a href="#" class="list-group-item list-group-item-action" onclick="selectAssetFromSearch(${asset.id})">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">${asset.name}</div>
                                    <small class="text-muted">${asset.category} • ${asset.barcode || 'No barcode'}</small>
                                </div>
                                <div>${statusBadge}</div>
                            </div>
                        </a>
                    `);
                });
            }
            
            results.show();
        });
}

function selectAssetFromSearch(assetId) {
    $.get(`/api/inventory/assets/${assetId}`)
        .done(function(asset) {
            selectAsset(asset);
        });
}

function clearAssetSelection() {
    $('#selected_asset_id').val('');
    $('#asset_search').val('');
    $('#selected_asset_details').hide();
    $('#asset_search_results').hide();
    $('#current_asset_status').html('<span class="badge bg-secondary">Not Selected</span>');
}

function calculateTotalCost() {
    const partsCost = parseFloat($('#parts_cost').val()) || 0;
    const laborCost = parseFloat($('#labor_cost').val()) || 0;
    const totalCost = partsCost + laborCost;
    
    if (totalCost > 0) {
        $('#estimated_cost').val(totalCost.toFixed(2));
    }
}

function getFormData() {
    return {
        asset_id: $('#selected_asset_id').val(),
        type: $('#maintenance_type').val(),
        priority: $('#priority').val(),
        scheduled_date: $('#scheduled_date').val(),
        estimated_duration: $('#estimated_duration').val(),
        description: $('#description').val(),
        assigned_technician: $('#assigned_technician').val(),
        external_vendor: $('#external_vendor').val(),
        maintenance_location: $('#maintenance_location').val(),
        estimated_cost: $('#estimated_cost').val(),
        parts_cost: $('#parts_cost').val(),
        labor_cost: $('#labor_cost').val(),
        safety_requirements: $('#safety_requirements').val(),
        notes: $('#notes').val(),
        is_recurring: $('#is_recurring').is(':checked'),
        recurring_frequency: $('#recurring_frequency').val(),
        recurring_interval: $('#recurring_interval').val(),
        recurring_end_date: $('#recurring_end_date').val(),
        send_notification: $('#send_notification').is(':checked'),
        send_reminders: $('#send_reminders').is(':checked'),
        reminder_days: $('#reminder_days').val()
    };
}

function updateMaintenance() {
    const formData = getFormData();
    const maintenanceId = $('#maintenanceId').val();
    
    // Validate required fields
    if (!formData.asset_id) {
        showToast('Please select an asset', 'error');
        return;
    }
    
    if (!formData.type || !formData.priority || !formData.scheduled_date || !formData.description) {
        showToast('Please fill in all required fields', 'error');
        return;
    }
    
    // Show loading state
    const submitBtn = $('#editMaintenanceForm button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
    
    $.ajax({
        url: `/api/inventory/maintenance/${maintenanceId}`,
        method: 'PUT',
        data: formData,
        success: function(response) {
            showToast('Maintenance updated successfully', 'success');
            
            // Redirect to maintenance details page
            setTimeout(() => {
                window.location.href = `/inventory/maintenance/${maintenanceId}`;
            }, 1500);
        },
        error: function(xhr) {
            let errorMessage = 'Error updating maintenance';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showToast(errorMessage, 'error');
        },
        complete: function() {
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
}

function saveAsDraft() {
    const formData = getFormData();
    const maintenanceId = $('#maintenanceId').val();
    
    formData.status = 'draft';
    
    $.ajax({
        url: `/api/inventory/maintenance/${maintenanceId}`,
        method: 'PUT',
        data: formData,
        success: function(response) {
            showToast('Maintenance saved as draft', 'success');
        },
        error: function(xhr) {
            let errorMessage = 'Error saving draft';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showToast(errorMessage, 'error');
        }
    });
}

function resetForm() {
    if (confirm('Are you sure you want to reset all changes? This will restore the original values.')) {
        populateForm(maintenanceData);
        showToast('Form reset to original values', 'info');
    }
}

// Utility functions
function getMaintenanceStatusBadge(status) {
    const badges = {
        'scheduled': '<span class="badge bg-primary">Scheduled</span>',
        'in_progress': '<span class="badge bg-warning">In Progress</span>',
        'completed': '<span class="badge bg-success">Completed</span>',
        'cancelled': '<span class="badge bg-secondary">Cancelled</span>',
        'overdue': '<span class="badge bg-danger">Overdue</span>',
        'draft': '<span class="badge bg-light text-dark">Draft</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function getMaintenanceTypeBadge(type) {
    const badges = {
        'preventive': '<span class="badge bg-success">Preventive</span>',
        'corrective': '<span class="badge bg-warning">Corrective</span>',
        'emergency': '<span class="badge bg-danger">Emergency</span>',
        'upgrade': '<span class="badge bg-info">Upgrade</span>'
    };
    return badges[type] || '<span class="badge bg-secondary">Unknown</span>';
}

function getAssetStatusBadge(status) {
    const badges = {
        'available': '<span class="badge bg-success">Available</span>',
        'allocated': '<span class="badge bg-warning">Allocated</span>',
        'maintenance': '<span class="badge bg-info">In Maintenance</span>',
        'retired': '<span class="badge bg-secondary">Retired</span>',
        'damaged': '<span class="badge bg-danger">Damaged</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function getActivityIcon(type) {
    const icons = {
        'created': 'fa-plus',
        'updated': 'fa-edit',
        'status_changed': 'fa-exchange-alt',
        'assigned': 'fa-user-plus',
        'completed': 'fa-check',
        'cancelled': 'fa-times',
        'rescheduled': 'fa-calendar-alt'
    };
    return icons[type] || 'fa-info';
}

function getActivityColor(type) {
    const colors = {
        'created': 'success',
        'updated': 'primary',
        'status_changed': 'warning',
        'assigned': 'info',
        'completed': 'success',
        'cancelled': 'secondary',
        'rescheduled': 'warning'
    };
    return colors[type] || 'secondary';
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString();
}

function formatDateTimeForInput(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showToast(message, type) {
    // Implementation for toast notifications
    console.log(`${type}: ${message}`);
}
</script>
@endsection