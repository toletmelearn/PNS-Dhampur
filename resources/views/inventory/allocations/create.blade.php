@extends('layouts.app')

@section('title', 'Allocate Asset')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Allocate Asset</h1>
            <p class="mb-0 text-muted">Assign an asset to an employee</p>
        </div>
        <div>
            <a href="{{ route('allocations.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Allocations
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Allocation Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Asset Allocation Details</h6>
                </div>
                <div class="card-body">
                    <form id="allocationForm" method="POST" action="{{ route('allocations.store') }}">
                        @csrf
                        
                        <!-- Asset Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Asset Information</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="inventory_item_id" class="form-label">Asset *</label>
                                <select class="form-select" id="inventory_item_id" name="inventory_item_id" required>
                                    <option value="">Select Asset</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="assetSearch" class="form-label">Search Asset</label>
                                <input type="text" class="form-control" id="assetSearch" placeholder="Search by name, barcode, or serial number">
                            </div>
                        </div>

                        <!-- Asset Details Display -->
                        <div id="assetDetails" class="row mb-4" style="display: none;">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <img id="assetImage" src="" alt="Asset Image" class="img-fluid rounded" style="max-height: 100px;">
                                            </div>
                                            <div class="col-md-9">
                                                <h6 id="assetName" class="mb-1"></h6>
                                                <p class="text-muted mb-1">
                                                    <strong>Category:</strong> <span id="assetCategory"></span> |
                                                    <strong>Barcode:</strong> <span id="assetBarcode"></span>
                                                </p>
                                                <p class="text-muted mb-1">
                                                    <strong>Serial Number:</strong> <span id="assetSerial"></span> |
                                                    <strong>Model:</strong> <span id="assetModel"></span>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <strong>Current Status:</strong> <span id="assetStatus"></span> |
                                                    <strong>Location:</strong> <span id="assetLocation"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Selection -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Employee Information</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="allocated_to" class="form-label">Employee *</label>
                                <select class="form-select" id="allocated_to" name="allocated_to" required>
                                    <option value="">Select Employee</option>
                                    <!-- Will be populated dynamically -->
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="employeeSearch" class="form-label">Search Employee</label>
                                <input type="text" class="form-control" id="employeeSearch" placeholder="Search by name, email, or employee ID">
                            </div>
                        </div>

                        <!-- Employee Details Display -->
                        <div id="employeeDetails" class="row mb-4" style="display: none;">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-user fa-2x text-white"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-10">
                                                <h6 id="employeeName" class="mb-1"></h6>
                                                <p class="text-muted mb-1">
                                                    <strong>Email:</strong> <span id="employeeEmail"></span> |
                                                    <strong>Department:</strong> <span id="employeeDepartment"></span>
                                                </p>
                                                <p class="text-muted mb-0">
                                                    <strong>Current Allocations:</strong> <span id="employeeAllocations"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Allocation Details -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Allocation Details</h6>
                            </div>
                            <div class="col-md-6">
                                <label for="allocated_date" class="form-label">Allocation Date *</label>
                                <input type="date" class="form-control" id="allocated_date" name="allocated_date" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="expected_return_date" class="form-label">Expected Return Date</label>
                                <input type="date" class="form-control" id="expected_return_date" name="expected_return_date">
                                <small class="form-text text-muted">Leave empty for indefinite allocation</small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="purpose" class="form-label">Purpose</label>
                                <input type="text" class="form-control" id="purpose" name="purpose" placeholder="Purpose of allocation">
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" placeholder="Where asset will be used">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="condition" class="form-label">Condition at Allocation</label>
                                <select class="form-select" id="condition" name="condition">
                                    <option value="excellent">Excellent</option>
                                    <option value="good" selected>Good</option>
                                    <option value="fair">Fair</option>
                                    <option value="poor">Poor</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Additional notes or instructions"></textarea>
                        </div>

                        <!-- Notification Settings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Notification Settings</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="send_notification" name="send_notification" checked>
                                    <label class="form-check-label" for="send_notification">
                                        Send notification to employee
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="send_reminder" name="send_reminder">
                                    <label class="form-check-label" for="send_reminder">
                                        Send return reminder before due date
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="reminderSettings" style="display: none;">
                            <div class="col-md-6">
                                <label for="reminder_days" class="form-label">Reminder Days Before</label>
                                <input type="number" class="form-control" id="reminder_days" name="reminder_days" value="3" min="1" max="30">
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                                            <i class="fas fa-save"></i> Save as Draft
                                        </button>
                                    </div>
                                    <div>
                                        <a href="{{ route('allocations.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check"></i> Allocate Asset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Tips -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Tips</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-success"><i class="fas fa-lightbulb"></i> Asset Selection</h6>
                        <p class="small text-muted mb-0">Only available assets (not currently allocated) will appear in the dropdown. Use the search function to quickly find specific assets.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-info"><i class="fas fa-calendar"></i> Return Dates</h6>
                        <p class="small text-muted mb-0">Setting an expected return date helps track asset usage and sends automatic reminders.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-warning"><i class="fas fa-bell"></i> Notifications</h6>
                        <p class="small text-muted mb-0">Enable notifications to keep employees informed about their asset allocations and return reminders.</p>
                    </div>
                    <div>
                        <h6 class="text-primary"><i class="fas fa-clipboard-check"></i> Condition</h6>
                        <p class="small text-muted mb-0">Document the asset condition at allocation to track any changes when returned.</p>
                    </div>
                </div>
            </div>

            <!-- Recent Allocations -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Allocations</h6>
                </div>
                <div class="card-body">
                    <div id="recentAllocations">
                        <!-- Will be populated dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirm Allocation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to allocate this asset?</p>
                <div id="allocationSummary">
                    <!-- Will be populated with allocation details -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAllocation">Confirm Allocation</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    loadAvailableAssets();
    loadEmployees();
    loadRecentAllocations();
    
    // Set default allocation date to today
    $('#allocated_date').val(new Date().toISOString().split('T')[0]);
    
    // Asset selection change
    $('#inventory_item_id').on('change', function() {
        const assetId = $(this).val();
        if (assetId) {
            loadAssetDetails(assetId);
        } else {
            $('#assetDetails').hide();
        }
    });
    
    // Employee selection change
    $('#allocated_to').on('change', function() {
        const employeeId = $(this).val();
        if (employeeId) {
            loadEmployeeDetails(employeeId);
        } else {
            $('#employeeDetails').hide();
        }
    });
    
    // Asset search functionality
    $('#assetSearch').on('input', debounce(function() {
        const query = $(this).val();
        if (query.length >= 2) {
            searchAssets(query);
        } else {
            loadAvailableAssets();
        }
    }, 300));
    
    // Employee search functionality
    $('#employeeSearch').on('input', debounce(function() {
        const query = $(this).val();
        if (query.length >= 2) {
            searchEmployees(query);
        } else {
            loadEmployees();
        }
    }, 300));
    
    // Reminder settings toggle
    $('#send_reminder').on('change', function() {
        if ($(this).is(':checked')) {
            $('#reminderSettings').show();
        } else {
            $('#reminderSettings').hide();
        }
    });
    
    // Form submission
    $('#allocationForm').on('submit', function(e) {
        e.preventDefault();
        showConfirmationModal();
    });
    
    // Confirm allocation
    $('#confirmAllocation').on('click', function() {
        submitAllocation();
    });
    
    // Auto-calculate expected return date based on asset type
    $('#inventory_item_id').on('change', function() {
        const assetId = $(this).val();
        if (assetId) {
            $.get(`/api/inventory-items/${assetId}/suggested-return-date`)
                .done(function(data) {
                    if (data.suggested_date) {
                        $('#expected_return_date').val(data.suggested_date);
                    }
                });
        }
    });
});

function loadAvailableAssets() {
    $.get('/api/inventory-items/available-assets')
        .done(function(assets) {
            const select = $('#inventory_item_id');
            select.find('option:not(:first)').remove();
            assets.forEach(asset => {
                select.append(`
                    <option value="${asset.id}" data-category="${asset.category?.name || ''}" data-barcode="${asset.barcode || ''}" data-serial="${asset.serial_number || ''}">
                        ${asset.name} ${asset.barcode ? `(${asset.barcode})` : ''}
                    </option>
                `);
            });
        })
        .fail(function() {
            showToast('Error loading available assets', 'error');
        });
}

function loadEmployees() {
    $.get('/api/employees')
        .done(function(employees) {
            const select = $('#allocated_to');
            select.find('option:not(:first)').remove();
            employees.forEach(employee => {
                select.append(`
                    <option value="${employee.id}" data-email="${employee.email || ''}" data-department="${employee.department || ''}">
                        ${employee.name}
                    </option>
                `);
            });
        })
        .fail(function() {
            showToast('Error loading employees', 'error');
        });
}

function loadAssetDetails(assetId) {
    $.get(`/api/inventory-items/${assetId}`)
        .done(function(asset) {
            $('#assetName').text(asset.name);
            $('#assetCategory').text(asset.category?.name || 'N/A');
            $('#assetBarcode').text(asset.barcode || 'N/A');
            $('#assetSerial').text(asset.serial_number || 'N/A');
            $('#assetModel').text(asset.model_number || 'N/A');
            $('#assetStatus').html(`<span class="badge bg-success">${asset.status}</span>`);
            $('#assetLocation').text(asset.location || 'N/A');
            
            if (asset.image_url) {
                $('#assetImage').attr('src', asset.image_url).show();
            } else {
                $('#assetImage').hide();
            }
            
            $('#assetDetails').show();
        })
        .fail(function() {
            showToast('Error loading asset details', 'error');
        });
}

function loadEmployeeDetails(employeeId) {
    $.get(`/api/employees/${employeeId}`)
        .done(function(employee) {
            $('#employeeName').text(employee.name);
            $('#employeeEmail').text(employee.email || 'N/A');
            $('#employeeDepartment').text(employee.department || 'N/A');
            $('#employeeAllocations').text(employee.active_allocations_count || 0);
            
            $('#employeeDetails').show();
        })
        .fail(function() {
            showToast('Error loading employee details', 'error');
        });
}

function searchAssets(query) {
    $.get('/api/inventory-items/search', { q: query, type: 'asset', available_only: true })
        .done(function(assets) {
            const select = $('#inventory_item_id');
            select.find('option:not(:first)').remove();
            assets.forEach(asset => {
                select.append(`
                    <option value="${asset.id}">
                        ${asset.name} ${asset.barcode ? `(${asset.barcode})` : ''}
                    </option>
                `);
            });
        });
}

function searchEmployees(query) {
    $.get('/api/employees/search', { q: query })
        .done(function(employees) {
            const select = $('#allocated_to');
            select.find('option:not(:first)').remove();
            employees.forEach(employee => {
                select.append(`
                    <option value="${employee.id}">
                        ${employee.name} - ${employee.email || ''}
                    </option>
                `);
            });
        });
}

function loadRecentAllocations() {
    $.get('/api/allocations/recent', { limit: 5 })
        .done(function(allocations) {
            const container = $('#recentAllocations');
            container.empty();
            
            if (allocations.length === 0) {
                container.append('<p class="text-muted small">No recent allocations</p>');
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

function showConfirmationModal() {
    const assetName = $('#inventory_item_id option:selected').text();
    const employeeName = $('#allocated_to option:selected').text();
    const allocatedDate = $('#allocated_date').val();
    const expectedReturnDate = $('#expected_return_date').val();
    const purpose = $('#purpose').val();
    
    const summary = `
        <div class="row">
            <div class="col-sm-4"><strong>Asset:</strong></div>
            <div class="col-sm-8">${assetName}</div>
        </div>
        <div class="row">
            <div class="col-sm-4"><strong>Employee:</strong></div>
            <div class="col-sm-8">${employeeName}</div>
        </div>
        <div class="row">
            <div class="col-sm-4"><strong>Date:</strong></div>
            <div class="col-sm-8">${new Date(allocatedDate).toLocaleDateString()}</div>
        </div>
        ${expectedReturnDate ? `
        <div class="row">
            <div class="col-sm-4"><strong>Return Date:</strong></div>
            <div class="col-sm-8">${new Date(expectedReturnDate).toLocaleDateString()}</div>
        </div>
        ` : ''}
        ${purpose ? `
        <div class="row">
            <div class="col-sm-4"><strong>Purpose:</strong></div>
            <div class="col-sm-8">${purpose}</div>
        </div>
        ` : ''}
    `;
    
    $('#allocationSummary').html(summary);
    $('#confirmationModal').modal('show');
}

function submitAllocation() {
    const formData = new FormData($('#allocationForm')[0]);
    
    $.ajax({
        url: '/api/allocations',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#confirmationModal').modal('hide');
            showToast('Asset allocated successfully', 'success');
            
            // Redirect to allocation details or back to index
            setTimeout(() => {
                window.location.href = `/inventory/allocations/${response.id}`;
            }, 1500);
        },
        error: function(xhr) {
            $('#confirmationModal').modal('hide');
            
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const input = $(`[name="${key}"]`);
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(errors[key][0]);
                });
                showToast('Please correct the form errors', 'error');
            } else {
                showToast(xhr.responseJSON?.message || 'Error allocating asset', 'error');
            }
        }
    });
}

function saveDraft() {
    const formData = new FormData($('#allocationForm')[0]);
    formData.append('is_draft', '1');
    
    $.ajax({
        url: '/api/allocations/draft',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            showToast('Draft saved successfully', 'success');
        },
        error: function() {
            showToast('Error saving draft', 'error');
        }
    });
}

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

// Utility functions
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