@extends('layouts.app')

@section('title', 'Schedule Maintenance')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Schedule Maintenance</h1>
            <p class="mb-0 text-muted">Schedule new maintenance for assets</p>
        </div>
        <div>
            <a href="{{ route('inventory.maintenance.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Maintenance
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Form -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Maintenance Details</h6>
                </div>
                <div class="card-body">
                    <form id="scheduleMaintenanceForm">
                        <!-- Asset Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Asset Information</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="asset_id" class="form-label">Asset *</label>
                                <select class="form-select" id="asset_id" name="asset_id" required>
                                    <option value="">Select Asset</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="form-text">Search and select the asset that needs maintenance</div>
                            </div>
                            <div class="col-md-6">
                                <label for="asset_search" class="form-label">Search Asset</label>
                                <input type="text" class="form-control" id="asset_search" placeholder="Search by name, barcode, or serial number">
                                <div class="form-text">Type to search for assets</div>
                            </div>
                        </div>

                        <!-- Selected Asset Info -->
                        <div id="selectedAssetInfo" class="row mb-4" style="display: none;">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <img id="assetImage" src="" alt="Asset Image" class="img-fluid rounded" style="max-height: 80px;">
                                            </div>
                                            <div class="col-md-10">
                                                <h6 id="assetName" class="mb-1"></h6>
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <small class="text-muted">Category:</small><br>
                                                        <span id="assetCategory"></span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <small class="text-muted">Barcode:</small><br>
                                                        <span id="assetBarcode"></span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <small class="text-muted">Serial Number:</small><br>
                                                        <span id="assetSerial"></span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <small class="text-muted">Location:</small><br>
                                                        <span id="assetLocation"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Maintenance Details -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Maintenance Details</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="maintenance_type" class="form-label">Maintenance Type *</label>
                                <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                                    <option value="">Select Type</option>
                                    <option value="preventive">Preventive Maintenance</option>
                                    <option value="corrective">Corrective Maintenance</option>
                                    <option value="emergency">Emergency Repair</option>
                                    <option value="inspection">Inspection</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Priority *</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="scheduled_date" class="form-label">Scheduled Date *</label>
                                <input type="datetime-local" class="form-control" id="scheduled_date" name="scheduled_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="estimated_duration" class="form-label">Estimated Duration (hours)</label>
                                <input type="number" class="form-control" id="estimated_duration" name="estimated_duration" step="0.5" min="0.5" placeholder="e.g., 2.5">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Describe the maintenance work to be performed, including specific tasks, procedures, or issues to address"></textarea>
                        </div>

                        <!-- Assignment -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Assignment & Resources</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="assigned_to" class="form-label">Assign To</label>
                                <select class="form-select" id="assigned_to" name="assigned_to">
                                    <option value="">Select Technician</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="vendor_id" class="form-label">External Vendor</label>
                                <select class="form-select" id="vendor_id" name="vendor_id">
                                    <option value="">Select Vendor (if external)</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                            </div>
                        </div>

                        <!-- Cost Estimation -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Cost Estimation</h6>
                            </div>
                            <div class="col-md-4">
                                <label for="estimated_cost" class="form-label">Estimated Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="estimated_cost" name="estimated_cost" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="parts_cost" class="form-label">Parts Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="parts_cost" name="parts_cost" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="labor_cost" class="form-label">Labor Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="labor_cost" name="labor_cost" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Additional Information</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Maintenance Location</label>
                                <input type="text" class="form-control" id="location" name="location" placeholder="Where will the maintenance be performed?">
                            </div>
                            <div class="col-md-6">
                                <label for="safety_requirements" class="form-label">Safety Requirements</label>
                                <input type="text" class="form-control" id="safety_requirements" name="safety_requirements" placeholder="Special safety considerations">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes, special instructions, or requirements"></textarea>
                        </div>

                        <!-- Recurring Maintenance -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Recurring Options</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="recurring" name="recurring">
                                    <label class="form-check-label" for="recurring">
                                        Set up recurring maintenance
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="recurringOptions" class="row mb-4" style="display: none;">
                            <div class="col-md-4">
                                <label for="recurring_frequency" class="form-label">Frequency</label>
                                <select class="form-select" id="recurring_frequency" name="recurring_frequency">
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="semi_annually">Semi-Annually</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="recurring_interval" class="form-label">Interval</label>
                                <input type="number" class="form-control" id="recurring_interval" name="recurring_interval" min="1" value="1" placeholder="Every X periods">
                            </div>
                            <div class="col-md-4">
                                <label for="recurring_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="recurring_end_date" name="recurring_end_date">
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Notifications</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="send_notification" name="send_notification" checked>
                                    <label class="form-check-label" for="send_notification">
                                        Send notification to assigned technician
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="reminder_enabled" name="reminder_enabled" checked>
                                    <label class="form-check-label" for="reminder_enabled">
                                        Send reminder notifications
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="reminderOptions" class="row mb-4">
                            <div class="col-md-6">
                                <label for="reminder_days" class="form-label">Reminder Days Before</label>
                                <select class="form-select" id="reminder_days" name="reminder_days">
                                    <option value="1">1 day before</option>
                                    <option value="3" selected>3 days before</option>
                                    <option value="7">1 week before</option>
                                    <option value="14">2 weeks before</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                                            <i class="fas fa-save"></i> Save as Draft
                                        </button>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">
                                            <i class="fas fa-undo"></i> Reset
                                        </button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-calendar-plus"></i> Schedule Maintenance
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Tips -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Tips</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-success"><i class="fas fa-lightbulb"></i> Asset Selection</h6>
                        <p class="small text-muted mb-2">Use the search field to quickly find assets by name, barcode, or serial number.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-info"><i class="fas fa-clock"></i> Scheduling</h6>
                        <p class="small text-muted mb-2">Schedule maintenance during off-peak hours to minimize disruption.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-warning"><i class="fas fa-tools"></i> Maintenance Types</h6>
                        <ul class="small text-muted mb-2">
                            <li><strong>Preventive:</strong> Regular scheduled maintenance</li>
                            <li><strong>Corrective:</strong> Fix known issues</li>
                            <li><strong>Emergency:</strong> Urgent repairs</li>
                            <li><strong>Inspection:</strong> Assessment only</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-primary"><i class="fas fa-bell"></i> Notifications</h6>
                        <p class="small text-muted mb-2">Enable notifications to keep technicians informed and set reminders to prevent missed maintenance.</p>
                    </div>
                </div>
            </div>

            <!-- Recent Maintenance -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Maintenance</h6>
                </div>
                <div class="card-body">
                    <div id="recentMaintenance">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">This Month</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <h4 class="text-primary" id="scheduledCount">0</h4>
                                <small class="text-muted">Scheduled</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success" id="completedCount">0</h4>
                            <small class="text-muted">Completed</small>
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
$(document).ready(function() {
    loadAssets();
    loadTechnicians();
    loadVendors();
    loadRecentMaintenance();
    loadMaintenanceStats();
    
    // Initialize event listeners
    initializeEventListeners();
    
    // Set default scheduled date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    $('#scheduled_date').val(tomorrow.toISOString().slice(0, 16));
});

function initializeEventListeners() {
    // Asset search functionality
    $('#asset_search').on('input', debounce(function() {
        const query = $(this).val();
        if (query.length >= 2) {
            searchAssets(query);
        }
    }, 300));
    
    // Asset selection change
    $('#asset_id').on('change', function() {
        const assetId = $(this).val();
        if (assetId) {
            loadAssetDetails(assetId);
        } else {
            $('#selectedAssetInfo').hide();
        }
    });
    
    // Recurring maintenance toggle
    $('#recurring').on('change', function() {
        if ($(this).is(':checked')) {
            $('#recurringOptions').show();
        } else {
            $('#recurringOptions').hide();
        }
    });
    
    // Reminder toggle
    $('#reminder_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#reminderOptions').show();
        } else {
            $('#reminderOptions').hide();
        }
    });
    
    // Form submission
    $('#scheduleMaintenanceForm').on('submit', function(e) {
        e.preventDefault();
        scheduleMaintenanceSubmit();
    });
    
    // Cost calculation
    $('#parts_cost, #labor_cost').on('input', function() {
        calculateTotalCost();
    });
}

function loadAssets() {
    $.get('/api/inventory-items', { type: 'asset', status: 'active' })
        .done(function(assets) {
            const select = $('#asset_id');
            select.find('option:not(:first)').remove();
            assets.forEach(asset => {
                select.append(`<option value="${asset.id}">${asset.name} (${asset.barcode || 'No barcode'})</option>`);
            });
        })
        .fail(function() {
            showToast('Error loading assets', 'error');
        });
}

function searchAssets(query) {
    $.get('/api/inventory-items/search', { q: query, type: 'asset' })
        .done(function(assets) {
            const select = $('#asset_id');
            select.find('option:not(:first)').remove();
            assets.forEach(asset => {
                select.append(`<option value="${asset.id}">${asset.name} (${asset.barcode || 'No barcode'})</option>`);
            });
        });
}

function loadAssetDetails(assetId) {
    $.get(`/api/inventory-items/${assetId}`)
        .done(function(asset) {
            $('#assetName').text(asset.name);
            $('#assetCategory').text(asset.category?.name || 'N/A');
            $('#assetBarcode').text(asset.barcode || 'N/A');
            $('#assetSerial').text(asset.serial_number || 'N/A');
            $('#assetLocation').text(asset.location || 'N/A');
            
            if (asset.image) {
                $('#assetImage').attr('src', asset.image).show();
            } else {
                $('#assetImage').attr('src', '/images/no-image.png').show();
            }
            
            $('#selectedAssetInfo').show();
            
            // Load maintenance history for this asset
            loadAssetMaintenanceHistory(assetId);
        })
        .fail(function() {
            showToast('Error loading asset details', 'error');
        });
}

function loadAssetMaintenanceHistory(assetId) {
    $.get(`/api/maintenance`, { asset_id: assetId, limit: 5 })
        .done(function(response) {
            // Display recent maintenance history in sidebar or modal
            console.log('Asset maintenance history:', response.data);
        });
}

function loadTechnicians() {
    $.get('/api/employees', { role: 'technician' })
        .done(function(technicians) {
            const select = $('#assigned_to');
            select.find('option:not(:first)').remove();
            technicians.forEach(tech => {
                select.append(`<option value="${tech.id}">${tech.name} - ${tech.department || 'N/A'}</option>`);
            });
        })
        .fail(function() {
            showToast('Error loading technicians', 'error');
        });
}

function loadVendors() {
    $.get('/api/vendors', { status: 'active' })
        .done(function(vendors) {
            const select = $('#vendor_id');
            select.find('option:not(:first)').remove();
            vendors.forEach(vendor => {
                select.append(`<option value="${vendor.id}">${vendor.name}</option>`);
            });
        })
        .fail(function() {
            showToast('Error loading vendors', 'error');
        });
}

function loadRecentMaintenance() {
    $.get('/api/maintenance', { limit: 5, sort: 'created_at', order: 'desc' })
        .done(function(response) {
            const container = $('#recentMaintenance');
            container.empty();
            
            if (response.data.length === 0) {
                container.html('<p class="text-muted text-center">No recent maintenance found</p>');
                return;
            }
            
            response.data.forEach(maintenance => {
                const statusBadge = getStatusBadge(maintenance.status);
                const priorityBadge = getPriorityBadge(maintenance.priority);
                
                container.append(`
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${maintenance.inventory_item.name}</h6>
                                <small class="text-muted">${maintenance.maintenance_type}</small>
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
            $('#recentMaintenance').html('<p class="text-muted text-center">Error loading recent maintenance</p>');
        });
}

function loadMaintenanceStats() {
    $.get('/api/maintenance/stats/monthly')
        .done(function(stats) {
            $('#scheduledCount').text(stats.scheduled || 0);
            $('#completedCount').text(stats.completed || 0);
        })
        .fail(function() {
            console.error('Error loading maintenance stats');
        });
}

function calculateTotalCost() {
    const partsCost = parseFloat($('#parts_cost').val()) || 0;
    const laborCost = parseFloat($('#labor_cost').val()) || 0;
    const totalCost = partsCost + laborCost;
    $('#estimated_cost').val(totalCost.toFixed(2));
}

function scheduleMaintenanceSubmit() {
    // Clear previous validation errors
    $('.form-control, .form-select').removeClass('is-invalid');
    
    const formData = new FormData($('#scheduleMaintenanceForm')[0]);
    
    $.ajax({
        url: '/api/maintenance',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            showToast('Maintenance scheduled successfully', 'success');
            
            // Redirect to maintenance details or list
            setTimeout(() => {
                window.location.href = `/inventory/maintenance/${response.id}`;
            }, 1500);
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const input = $(`[name="${key}"]`);
                    input.addClass('is-invalid');
                    
                    // Show error message
                    const errorDiv = input.next('.invalid-feedback');
                    if (errorDiv.length) {
                        errorDiv.text(errors[key][0]);
                    } else {
                        input.after(`<div class="invalid-feedback">${errors[key][0]}</div>`);
                    }
                });
                showToast('Please correct the form errors', 'error');
            } else {
                showToast(xhr.responseJSON?.message || 'Error scheduling maintenance', 'error');
            }
        }
    });
}

function saveDraft() {
    const formData = new FormData($('#scheduleMaintenanceForm')[0]);
    formData.append('status', 'draft');
    
    $.ajax({
        url: '/api/maintenance',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            showToast('Draft saved successfully', 'success');
        },
        error: function(xhr) {
            showToast(xhr.responseJSON?.message || 'Error saving draft', 'error');
        }
    });
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        $('#scheduleMaintenanceForm')[0].reset();
        $('#selectedAssetInfo').hide();
        $('#recurringOptions').hide();
        $('#reminderOptions').show();
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Reset default values
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        $('#scheduled_date').val(tomorrow.toISOString().slice(0, 16));
        $('#priority').val('medium');
        $('#send_notification').prop('checked', true);
        $('#reminder_enabled').prop('checked', true);
    }
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

function showToast(message, type = 'info') {
    // Implement your toast notification system here
    console.log(`${type.toUpperCase()}: ${message}`);
}
</script>
@endsection