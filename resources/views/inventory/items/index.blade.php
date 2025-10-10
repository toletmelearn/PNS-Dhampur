@extends('layouts.app')

@section('title', 'Inventory Items')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Inventory Items</h1>
                    <p class="text-muted">Manage your inventory items and stock levels</p>
                </div>
                <div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-file-import"></i> Import
                    </button>
                    <button class="btn btn-outline-secondary" onclick="exportItems()">
                        <i class="fas fa-file-export"></i> Export
                    </button>
                    <a href="{{ route('inventory.items.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Item
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filters
                <button class="btn btn-sm btn-outline-primary float-right" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </h6>
        </div>
        <div class="collapse" id="filtersCollapse">
            <div class="card-body">
                <form id="filtersForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="searchFilter" class="form-label">Search</label>
                                <input type="text" class="form-control" id="searchFilter" name="search" placeholder="Search items...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="categoryFilter" class="form-label">Category</label>
                                <select class="form-select" id="categoryFilter" name="category_id">
                                    <option value="">All Categories</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select class="form-select" id="statusFilter" name="status">
                                    <option value="">All Status</option>
                                    <option value="available">Available</option>
                                    <option value="allocated">Allocated</option>
                                    <option value="maintenance">Under Maintenance</option>
                                    <option value="damaged">Damaged</option>
                                    <option value="lost">Lost</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="stockFilter" class="form-label">Stock Level</label>
                                <select class="form-select" id="stockFilter" name="stock_level">
                                    <option value="">All Levels</option>
                                    <option value="in_stock">In Stock</option>
                                    <option value="low_stock">Low Stock</option>
                                    <option value="out_of_stock">Out of Stock</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="assetFilter" class="form-label">Type</label>
                                <select class="form-select" id="assetFilter" name="is_asset">
                                    <option value="">All Types</option>
                                    <option value="1">Assets</option>
                                    <option value="0">Consumables</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="priceMinFilter" class="form-label">Min Price</label>
                                <input type="number" class="form-control" id="priceMinFilter" name="price_min" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="priceMaxFilter" class="form-label">Max Price</label>
                                <input type="number" class="form-control" id="priceMaxFilter" name="price_max" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="sortFilter" class="form-label">Sort By</label>
                                <select class="form-select" id="sortFilter" name="sort">
                                    <option value="name">Name</option>
                                    <option value="created_at">Date Added</option>
                                    <option value="current_stock">Stock Level</option>
                                    <option value="unit_price">Price</option>
                                    <option value="category">Category</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Items List</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    <div class="dropdown-header">Bulk Actions:</div>
                    <a class="dropdown-item" href="#" onclick="bulkAction('delete')">
                        <i class="fas fa-trash fa-sm fa-fw mr-2 text-gray-400"></i>
                        Delete Selected
                    </a>
                    <a class="dropdown-item" href="#" onclick="bulkAction('export')">
                        <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i>
                        Export Selected
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="itemsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="30">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Barcode</th>
                            <th>Current Stock</th>
                            <th>Min Stock</th>
                            <th>Unit Price</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <tr>
                            <td colspan="10" class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <span id="paginationInfo">Showing 0 to 0 of 0 entries</span>
                </div>
                <nav>
                    <ul class="pagination mb-0" id="pagination">
                        <!-- Pagination will be populated by JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">Select CSV File</label>
                        <input type="file" class="form-control" id="importFile" name="file" accept=".csv" required>
                        <div class="form-text">
                            <a href="{{ route('inventory.items.template') }}" class="text-decoration-none">
                                <i class="fas fa-download"></i> Download Template
                            </a>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="updateExisting" name="update_existing">
                        <label class="form-check-label" for="updateExisting">
                            Update existing items
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this item? This action cannot be undone.</p>
                <div id="deleteItemInfo"></div>
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
let currentFilters = {};
let itemToDelete = null;

$(document).ready(function() {
    loadItems();
    loadCategories();
    
    // Initialize filters form
    $('#filtersForm').on('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });
    
    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('input[name="selected_items[]"]').prop('checked', this.checked);
    });
    
    // Import form
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        importItems();
    });
    
    // Delete confirmation
    $('#confirmDelete').on('click', function() {
        if (itemToDelete) {
            deleteItem(itemToDelete);
        }
    });
});

function loadItems(page = 1) {
    currentPage = page;
    const params = new URLSearchParams({
        page: page,
        ...currentFilters
    });
    
    $.ajax({
        url: '{{ route("inventory.items.index") }}?' + params.toString(),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateItemsTable(response.data.data);
                updatePagination(response.data);
            }
        },
        error: function(xhr) {
            console.error('Error loading items:', xhr);
            showToast('Error loading items', 'error');
        }
    });
}

function updateItemsTable(items) {
    let html = '';
    
    if (items.length === 0) {
        html = '<tr><td colspan="10" class="text-center text-muted">No items found</td></tr>';
    } else {
        items.forEach(item => {
            const stockClass = getStockClass(item.current_stock, item.minimum_stock);
            const statusBadge = getStatusBadge(item.status);
            const typeBadge = item.is_asset ? '<span class="badge bg-info">Asset</span>' : '<span class="badge bg-secondary">Consumable</span>';
            
            html += `
                <tr>
                    <td>
                        <input type="checkbox" name="selected_items[]" value="${item.id}">
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div>
                                <div class="font-weight-bold">${item.name}</div>
                                ${item.description ? `<small class="text-muted">${item.description.substring(0, 50)}${item.description.length > 50 ? '...' : ''}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${item.category ? item.category.name : 'N/A'}</td>
                    <td>${item.barcode || 'N/A'}</td>
                    <td>
                        <span class="badge ${stockClass}">${item.current_stock}</span>
                    </td>
                    <td>${item.minimum_stock}</td>
                    <td>₹${parseFloat(item.unit_price).toLocaleString()}</td>
                    <td>${statusBadge}</td>
                    <td>${typeBadge}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('inventory.items.show', '') }}/${item.id}" class="btn btn-sm btn-outline-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('inventory.items.edit', '') }}/${item.id}" class="btn btn-sm btn-outline-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${item.id}, '${item.name}')" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#itemsTableBody').html(html);
}

function getStockClass(currentStock, minimumStock) {
    if (currentStock === 0) return 'bg-danger';
    if (currentStock <= minimumStock) return 'bg-warning';
    return 'bg-success';
}

function getStatusBadge(status) {
    const badges = {
        'available': '<span class="badge bg-success">Available</span>',
        'allocated': '<span class="badge bg-primary">Allocated</span>',
        'maintenance': '<span class="badge bg-warning">Maintenance</span>',
        'damaged': '<span class="badge bg-danger">Damaged</span>',
        'lost': '<span class="badge bg-dark">Lost</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

function updatePagination(data) {
    const { current_page, last_page, from, to, total } = data;
    
    // Update pagination info
    $('#paginationInfo').text(`Showing ${from || 0} to ${to || 0} of ${total} entries`);
    
    // Update pagination links
    let paginationHtml = '';
    
    // Previous button
    if (current_page > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(${current_page - 1})">Previous</a></li>`;
    } else {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
    }
    
    // Page numbers
    const startPage = Math.max(1, current_page - 2);
    const endPage = Math.min(last_page, current_page + 2);
    
    if (startPage > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(1)">1</a></li>`;
        if (startPage > 2) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === current_page) {
            paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(${i})">${i}</a></li>`;
        }
    }
    
    if (endPage < last_page) {
        if (endPage < last_page - 1) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(${last_page})">${last_page}</a></li>`;
    }
    
    // Next button
    if (current_page < last_page) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadItems(${current_page + 1})">Next</a></li>`;
    } else {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
    }
    
    $('#pagination').html(paginationHtml);
}

function loadCategories() {
    $.ajax({
        url: '{{ route("inventory.categories.index") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">All Categories</option>';
                response.data.data.forEach(category => {
                    options += `<option value="${category.id}">${category.name}</option>`;
                });
                $('#categoryFilter').html(options);
            }
        }
    });
}

function applyFilters() {
    currentFilters = {};
    const formData = new FormData(document.getElementById('filtersForm'));
    
    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            currentFilters[key] = value;
        }
    }
    
    loadItems(1);
}

function clearFilters() {
    $('#filtersForm')[0].reset();
    currentFilters = {};
    loadItems(1);
}

function confirmDelete(id, name) {
    itemToDelete = id;
    $('#deleteItemInfo').html(`<strong>Item:</strong> ${name}`);
    $('#deleteModal').modal('show');
}

function deleteItem(id) {
    $.ajax({
        url: `{{ route('inventory.items.destroy', '') }}/${id}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#deleteModal').modal('hide');
                loadItems(currentPage);
                showToast('Item deleted successfully', 'success');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Error deleting item';
            showToast(message, 'error');
        }
    });
}

function bulkAction(action) {
    const selectedItems = $('input[name="selected_items[]"]:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedItems.length === 0) {
        showToast('Please select items first', 'warning');
        return;
    }
    
    if (action === 'delete') {
        if (confirm(`Are you sure you want to delete ${selectedItems.length} selected items?`)) {
            bulkDelete(selectedItems);
        }
    } else if (action === 'export') {
        bulkExport(selectedItems);
    }
}

function bulkDelete(ids) {
    $.ajax({
        url: '{{ route("inventory.items.bulk-delete") }}',
        method: 'POST',
        data: {
            ids: ids,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                loadItems(currentPage);
                $('#selectAll').prop('checked', false);
                showToast(`${response.deleted_count} items deleted successfully`, 'success');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Error deleting items';
            showToast(message, 'error');
        }
    });
}

function bulkExport(ids) {
    const params = new URLSearchParams({
        ids: ids.join(','),
        format: 'csv'
    });
    
    window.open(`{{ route('inventory.items.export') }}?${params.toString()}`, '_blank');
}

function exportItems() {
    const params = new URLSearchParams(currentFilters);
    params.append('format', 'csv');
    
    window.open(`{{ route('inventory.items.export') }}?${params.toString()}`, '_blank');
}

function importItems() {
    const formData = new FormData(document.getElementById('importForm'));
    
    $.ajax({
        url: '{{ route("inventory.items.import") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#importModal').modal('hide');
                $('#importForm')[0].reset();
                loadItems(currentPage);
                showToast(`${response.imported_count} items imported successfully`, 'success');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Error importing items';
            showToast(message, 'error');
        }
    });
}

function showToast(message, type) {
    const toast = $(`
        <div class="alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(() => {
        toast.alert('close');
    }, 5000);
}
</script>
@endsection