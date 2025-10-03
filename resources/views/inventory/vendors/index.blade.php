@extends('layouts.app')

@section('title', 'Vendor Management')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Vendor Management</h1>
            <p class="mb-0 text-muted">Manage suppliers and vendor relationships</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#importVendorsModal">
                <i class="fas fa-upload"></i> Import
            </button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addVendorModal">
                <i class="fas fa-plus"></i> Add Vendor
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Vendors</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalVendors">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Vendors</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeVendors">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Top Rated</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="topRatedVendors">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalVendorItems">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters & Search</h6>
        </div>
        <div class="card-body">
            <form id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="searchVendor" class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchVendor" placeholder="Search vendors...">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="ratingFilter" class="form-label">Rating</label>
                        <select class="form-select" id="ratingFilter">
                            <option value="">All Ratings</option>
                            <option value="5">5 Stars</option>
                            <option value="4">4+ Stars</option>
                            <option value="3">3+ Stars</option>
                            <option value="2">2+ Stars</option>
                            <option value="1">1+ Stars</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="typeFilter" class="form-label">Type</label>
                        <select class="form-select" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="supplier">Supplier</option>
                            <option value="manufacturer">Manufacturer</option>
                            <option value="distributor">Distributor</option>
                            <option value="service_provider">Service Provider</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="sortBy" class="form-label">Sort By</label>
                        <select class="form-select" id="sortBy">
                            <option value="name">Name</option>
                            <option value="rating">Rating</option>
                            <option value="items_count">Items Count</option>
                            <option value="created_at">Date Added</option>
                            <option value="last_order_date">Last Order</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                        <button type="button" class="btn btn-info" onclick="exportVendors()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Vendors List</h6>
            <div>
                <div class="btn-group" role="group">
                    <input type="checkbox" class="btn-check" id="selectAll" autocomplete="off">
                    <label class="btn btn-outline-secondary btn-sm" for="selectAll">Select All</label>
                </div>
                <div class="btn-group ms-2" role="group">
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="bulkAction('activate')" disabled id="bulkActivate">
                        <i class="fas fa-check"></i> Activate
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="bulkAction('deactivate')" disabled id="bulkDeactivate">
                        <i class="fas fa-pause"></i> Deactivate
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkAction('delete')" disabled id="bulkDelete">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="vendorsTable">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="selectAllTable"></th>
                            <th>Vendor</th>
                            <th>Contact</th>
                            <th>Type</th>
                            <th>Rating</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Last Order</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="vendorsTableBody">
                        <!-- Dynamic content will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Vendors pagination">
                <ul class="pagination justify-content-center" id="vendorsPagination">
                    <!-- Pagination will be generated dynamically -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Add/Edit Vendor Modal -->
<div class="modal fade" id="addVendorModal" tabindex="-1" aria-labelledby="addVendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVendorModalLabel">Add New Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="vendorForm">
                <div class="modal-body">
                    <input type="hidden" id="vendorId" name="vendor_id">
                    
                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="vendorName" class="form-label">Vendor Name *</label>
                            <input type="text" class="form-control" id="vendorName" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="vendorCode" class="form-label">Vendor Code</label>
                            <input type="text" class="form-control" id="vendorCode" name="code">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="vendorType" class="form-label">Type *</label>
                            <select class="form-select" id="vendorType" name="type" required>
                                <option value="">Select Type</option>
                                <option value="supplier">Supplier</option>
                                <option value="manufacturer">Manufacturer</option>
                                <option value="distributor">Distributor</option>
                                <option value="service_provider">Service Provider</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="vendorStatus" class="form-label">Status</label>
                            <select class="form-select" id="vendorStatus" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <h6 class="mb-3">Contact Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contactPerson" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contactPerson" name="contact_person">
                        </div>
                        <div class="col-md-6">
                            <label for="contactEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="contactEmail" name="email">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contactPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="contactPhone" name="phone">
                        </div>
                        <div class="col-md-6">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>

                    <!-- Business Information -->
                    <h6 class="mb-3">Business Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="taxId" class="form-label">Tax ID/GST Number</label>
                            <input type="text" class="form-control" id="taxId" name="tax_id">
                        </div>
                        <div class="col-md-6">
                            <label for="paymentTerms" class="form-label">Payment Terms (Days)</label>
                            <input type="number" class="form-control" id="paymentTerms" name="payment_terms" min="0">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="creditLimit" class="form-label">Credit Limit</label>
                            <input type="number" class="form-control" id="creditLimit" name="credit_limit" min="0" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <option value="INR">INR (₹)</option>
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Vendors Modal -->
<div class="modal fade" id="importVendorsModal" tabindex="-1" aria-labelledby="importVendorsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importVendorsModalLabel">Import Vendors</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importVendorsForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="vendorsFile" class="form-label">Select CSV File</label>
                        <input type="file" class="form-control" id="vendorsFile" name="file" accept=".csv" required>
                        <div class="form-text">
                            Upload a CSV file with vendor data. 
                            <a href="/templates/vendors-template.csv" download>Download template</a>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="updateExisting" name="update_existing">
                        <label class="form-check-label" for="updateExisting">
                            Update existing vendors if found
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import Vendors</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the selected vendor(s)? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> Deleting vendors may affect related inventory items and purchase orders.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentPage = 1;
let selectedVendors = [];

$(document).ready(function() {
    loadVendorsSummary();
    loadVendors();
    
    // Initialize form validation
    $('#vendorForm').on('submit', handleVendorSubmit);
    $('#importVendorsForm').on('submit', handleImportSubmit);
    
    // Search functionality
    $('#searchVendor').on('input', debounce(function() {
        currentPage = 1;
        loadVendors();
    }, 300));
    
    // Select all functionality
    $('#selectAllTable').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.vendor-checkbox').prop('checked', isChecked);
        updateSelectedVendors();
    });
    
    // Individual checkbox change
    $(document).on('change', '.vendor-checkbox', function() {
        updateSelectedVendors();
    });
});

function loadVendorsSummary() {
    $.get('/api/vendors/summary')
        .done(function(data) {
            $('#totalVendors').text(data.total || 0);
            $('#activeVendors').text(data.active || 0);
            $('#topRatedVendors').text(data.top_rated || 0);
            $('#totalVendorItems').text(data.total_items || 0);
        })
        .fail(function() {
            showToast('Error loading vendor summary', 'error');
        });
}

function loadVendors(page = 1) {
    const filters = {
        search: $('#searchVendor').val(),
        status: $('#statusFilter').val(),
        rating: $('#ratingFilter').val(),
        type: $('#typeFilter').val(),
        sort_by: $('#sortBy').val(),
        page: page
    };
    
    $.get('/api/vendors', filters)
        .done(function(data) {
            renderVendorsTable(data.data);
            renderPagination(data);
            currentPage = page;
        })
        .fail(function() {
            showToast('Error loading vendors', 'error');
        });
}

function renderVendorsTable(vendors) {
    const tbody = $('#vendorsTableBody');
    tbody.empty();
    
    if (vendors.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="9" class="text-center py-4">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No vendors found</p>
                </td>
            </tr>
        `);
        return;
    }
    
    vendors.forEach(vendor => {
        const statusBadge = getStatusBadge(vendor.status);
        const ratingStars = getRatingStars(vendor.rating);
        const lastOrder = vendor.last_order_date ? 
            new Date(vendor.last_order_date).toLocaleDateString() : 'Never';
        
        tbody.append(`
            <tr>
                <td>
                    <input type="checkbox" class="vendor-checkbox" value="${vendor.id}">
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                            <i class="fas fa-building text-white"></i>
                        </div>
                        <div>
                            <div class="fw-bold">${vendor.name}</div>
                            <small class="text-muted">${vendor.code || 'No code'}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <div>${vendor.contact_person || 'N/A'}</div>
                        <small class="text-muted">${vendor.email || ''}</small>
                        <small class="text-muted d-block">${vendor.phone || ''}</small>
                    </div>
                </td>
                <td>
                    <span class="badge bg-info">${vendor.type.replace('_', ' ').toUpperCase()}</span>
                </td>
                <td>${ratingStars}</td>
                <td>
                    <span class="badge bg-secondary">${vendor.items_count || 0}</span>
                </td>
                <td>${statusBadge}</td>
                <td>${lastOrder}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewVendor(${vendor.id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="editVendor(${vendor.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteVendor(${vendor.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });
}

function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">Active</span>',
        'inactive': '<span class="badge bg-secondary">Inactive</span>',
        'suspended': '<span class="badge bg-danger">Suspended</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function getRatingStars(rating) {
    if (!rating) return '<span class="text-muted">No rating</span>';
    
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<i class="fas fa-star text-warning"></i>';
        } else {
            stars += '<i class="far fa-star text-muted"></i>';
        }
    }
    return `${stars} <small>(${rating})</small>`;
}

function renderPagination(data) {
    const pagination = $('#vendorsPagination');
    pagination.empty();
    
    if (data.last_page <= 1) return;
    
    // Previous button
    pagination.append(`
        <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadVendors(${data.current_page - 1})">Previous</a>
        </li>
    `);
    
    // Page numbers
    for (let i = 1; i <= data.last_page; i++) {
        if (i === data.current_page || 
            i === 1 || 
            i === data.last_page || 
            (i >= data.current_page - 1 && i <= data.current_page + 1)) {
            pagination.append(`
                <li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadVendors(${i})">${i}</a>
                </li>
            `);
        } else if (i === data.current_page - 2 || i === data.current_page + 2) {
            pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }
    
    // Next button
    pagination.append(`
        <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadVendors(${data.current_page + 1})">Next</a>
        </li>
    `);
}

function applyFilters() {
    currentPage = 1;
    loadVendors();
}

function clearFilters() {
    $('#filterForm')[0].reset();
    currentPage = 1;
    loadVendors();
}

function updateSelectedVendors() {
    selectedVendors = $('.vendor-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    const hasSelection = selectedVendors.length > 0;
    $('#bulkActivate, #bulkDeactivate, #bulkDelete').prop('disabled', !hasSelection);
    
    // Update select all checkbox
    const totalCheckboxes = $('.vendor-checkbox').length;
    const checkedCheckboxes = $('.vendor-checkbox:checked').length;
    $('#selectAllTable').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
    $('#selectAllTable').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
}

function handleVendorSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const vendorId = $('#vendorId').val();
    const url = vendorId ? `/api/vendors/${vendorId}` : '/api/vendors';
    const method = vendorId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#addVendorModal').modal('hide');
            showToast(vendorId ? 'Vendor updated successfully' : 'Vendor created successfully', 'success');
            loadVendors(currentPage);
            loadVendorsSummary();
            $('#vendorForm')[0].reset();
            $('#vendorId').val('');
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                Object.keys(errors).forEach(key => {
                    showToast(errors[key][0], 'error');
                });
            } else {
                showToast('Error saving vendor', 'error');
            }
        }
    });
}

function handleImportSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '/api/vendors/import',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#importVendorsModal').modal('hide');
            showToast(`Successfully imported ${response.imported} vendors`, 'success');
            loadVendors(currentPage);
            loadVendorsSummary();
            $('#importVendorsForm')[0].reset();
        },
        error: function(xhr) {
            showToast(xhr.responseJSON?.message || 'Error importing vendors', 'error');
        }
    });
}

function viewVendor(id) {
    window.location.href = `/inventory/vendors/${id}`;
}

function editVendor(id) {
    $.get(`/api/vendors/${id}`)
        .done(function(vendor) {
            $('#vendorId').val(vendor.id);
            $('#vendorName').val(vendor.name);
            $('#vendorCode').val(vendor.code);
            $('#vendorType').val(vendor.type);
            $('#vendorStatus').val(vendor.status);
            $('#contactPerson').val(vendor.contact_person);
            $('#contactEmail').val(vendor.email);
            $('#contactPhone').val(vendor.phone);
            $('#website').val(vendor.website);
            $('#address').val(vendor.address);
            $('#taxId').val(vendor.tax_id);
            $('#paymentTerms').val(vendor.payment_terms);
            $('#creditLimit').val(vendor.credit_limit);
            $('#currency').val(vendor.currency);
            $('#notes').val(vendor.notes);
            
            $('#addVendorModalLabel').text('Edit Vendor');
            $('#addVendorModal').modal('show');
        })
        .fail(function() {
            showToast('Error loading vendor details', 'error');
        });
}

function deleteVendor(id) {
    selectedVendors = [id];
    $('#deleteConfirmModal').modal('show');
}

function bulkAction(action) {
    if (selectedVendors.length === 0) {
        showToast('Please select vendors first', 'warning');
        return;
    }
    
    if (action === 'delete') {
        $('#deleteConfirmModal').modal('show');
        return;
    }
    
    const actionText = action === 'activate' ? 'activated' : 'deactivated';
    
    $.ajax({
        url: '/api/vendors/bulk-action',
        method: 'POST',
        data: {
            action: action,
            vendor_ids: selectedVendors
        },
        success: function(response) {
            showToast(`Successfully ${actionText} ${selectedVendors.length} vendor(s)`, 'success');
            loadVendors(currentPage);
            loadVendorsSummary();
            selectedVendors = [];
            updateSelectedVendors();
        },
        error: function() {
            showToast(`Error ${action}ing vendors`, 'error');
        }
    });
}

function exportVendors() {
    const filters = {
        search: $('#searchVendor').val(),
        status: $('#statusFilter').val(),
        rating: $('#ratingFilter').val(),
        type: $('#typeFilter').val(),
        sort_by: $('#sortBy').val()
    };
    
    const queryString = new URLSearchParams(filters).toString();
    window.open(`/api/vendors/export?${queryString}`, '_blank');
}

// Confirm delete action
$('#confirmDelete').on('click', function() {
    $.ajax({
        url: '/api/vendors/bulk-delete',
        method: 'DELETE',
        data: {
            vendor_ids: selectedVendors
        },
        success: function(response) {
            $('#deleteConfirmModal').modal('hide');
            showToast(`Successfully deleted ${selectedVendors.length} vendor(s)`, 'success');
            loadVendors(currentPage);
            loadVendorsSummary();
            selectedVendors = [];
            updateSelectedVendors();
        },
        error: function() {
            showToast('Error deleting vendors', 'error');
        }
    });
});

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