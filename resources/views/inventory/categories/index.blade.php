@extends('layouts.app')

@section('title', 'Inventory Categories')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Inventory Categories</h1>
                    <p class="text-muted">Manage your inventory categories and subcategories</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalCategories">{{ $categories->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tags fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeCategories">{{ $categories->where('is_active', true)->count() }}</div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Parent Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="parentCategories">{{ $categories->whereNull('parent_id')->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sitemap fa-2x text-gray-300"></i>
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalItems">{{ $categories->sum('items_count') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="m-0 font-weight-bold text-primary">Categories</h6>
                </div>
                <div class="col-auto">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="treeViewBtn">
                            <i class="fas fa-sitemap"></i> Tree View
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm active" id="listViewBtn">
                            <i class="fas fa-list"></i> List View
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Search and Filters -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search categories...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="parent">Parent Categories</option>
                        <option value="child">Subcategories</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <!-- List View -->
            <div id="listView">
                <div class="table-responsive">
                    <table class="table table-bordered" id="categoriesTable">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>Name</th>
                                <th>Parent Category</th>
                                <th>Items Count</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                            <tr data-category-id="{{ $category->id }}">
                                <td>
                                    <input type="checkbox" class="category-checkbox" value="{{ $category->id }}">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($category->icon)
                                            <i class="{{ $category->icon }} me-2 text-primary"></i>
                                        @endif
                                        <div>
                                            <strong>{{ $category->name }}</strong>
                                            @if($category->description)
                                                <br><small class="text-muted">{{ Str::limit($category->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($category->parent)
                                        <span class="badge bg-secondary">{{ $category->parent->name }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $category->items_count ?? 0 }}</span>
                                </td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $category->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editCategory({{ $category->id }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewCategory({{ $category->id }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory({{ $category->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} results
                    </div>
                    <div>
                        {{ $categories->links() }}
                    </div>
                </div>
            </div>

            <!-- Tree View -->
            <div id="treeView" style="display: none;">
                <div id="categoryTree">
                    <!-- Tree will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card shadow" id="bulkActionsCard" style="display: none;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col">
                    <span id="selectedCount">0</span> categories selected
                </div>
                <div class="col-auto">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-success" onclick="bulkActivate()">
                            <i class="fas fa-check"></i> Activate
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="bulkDeactivate()">
                            <i class="fas fa-times"></i> Deactivate
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="bulkDelete()">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" id="categoryId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoryName" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="categoryName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoryCode" class="form-label">Code</label>
                                <input type="text" class="form-control" id="categoryCode" name="code" placeholder="AUTO">
                                <small class="form-text text-muted">Leave blank for auto-generation</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="parentCategory" class="form-label">Parent Category</label>
                                <select class="form-select" id="parentCategory" name="parent_id">
                                    <option value="">None (Root Category)</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoryIcon" class="form-label">Icon</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i id="iconPreview" class="fas fa-tag"></i>
                                    </span>
                                    <input type="text" class="form-control" id="categoryIcon" name="icon" placeholder="fas fa-tag">
                                </div>
                                <small class="form-text text-muted">FontAwesome icon class</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="categoryDescription" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sortOrder" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sortOrder" name="sort_order" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                                    <label class="form-check-label" for="isActive">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Properties -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Category Properties</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allowAssets" name="allow_assets">
                                        <label class="form-check-label" for="allowAssets">
                                            Allow Assets
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allowConsumables" name="allow_consumables" checked>
                                        <label class="form-check-label" for="allowConsumables">
                                            Allow Consumables
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requiresApproval" name="requires_approval">
                                        <label class="form-check-label" for="requiresApproval">
                                            Requires Approval for Changes
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="trackSerial" name="track_serial">
                                        <label class="form-check-label" for="trackSerial">
                                            Track Serial Numbers
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Category Details Modal -->
<div class="modal fade" id="categoryDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Category Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="categoryDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize
    initializeFilters();
    initializeCheckboxes();
    
    // Form submission
    $('#categoryForm').on('submit', function(e) {
        e.preventDefault();
        saveCategory();
    });
    
    // Icon preview
    $('#categoryIcon').on('input', function() {
        const iconClass = $(this).val() || 'fas fa-tag';
        $('#iconPreview').attr('class', iconClass);
    });
    
    // View toggle
    $('#treeViewBtn').on('click', function() {
        showTreeView();
    });
    
    $('#listViewBtn').on('click', function() {
        showListView();
    });
});

function initializeFilters() {
    $('#searchInput, #statusFilter, #typeFilter').on('change keyup', function() {
        filterCategories();
    });
}

function initializeCheckboxes() {
    $('#selectAll').on('change', function() {
        $('.category-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActions();
    });
    
    $('.category-checkbox').on('change', function() {
        updateBulkActions();
    });
}

function filterCategories() {
    const search = $('#searchInput').val().toLowerCase();
    const status = $('#statusFilter').val();
    const type = $('#typeFilter').val();
    
    $('#categoriesTable tbody tr').each(function() {
        const row = $(this);
        const name = row.find('td:nth-child(2)').text().toLowerCase();
        const parentCell = row.find('td:nth-child(3)').text();
        const statusCell = row.find('td:nth-child(5) .badge').hasClass('bg-success');
        
        let show = true;
        
        // Search filter
        if (search && !name.includes(search)) {
            show = false;
        }
        
        // Status filter
        if (status !== '') {
            const isActive = statusCell;
            if ((status === '1' && !isActive) || (status === '0' && isActive)) {
                show = false;
            }
        }
        
        // Type filter
        if (type === 'parent' && parentCell !== '-') {
            show = false;
        } else if (type === 'child' && parentCell === '-') {
            show = false;
        }
        
        row.toggle(show);
    });
}

function resetFilters() {
    $('#searchInput').val('');
    $('#statusFilter').val('');
    $('#typeFilter').val('');
    filterCategories();
}

function updateBulkActions() {
    const selectedCount = $('.category-checkbox:checked').length;
    $('#selectedCount').text(selectedCount);
    
    if (selectedCount > 0) {
        $('#bulkActionsCard').show();
    } else {
        $('#bulkActionsCard').hide();
    }
}

function showTreeView() {
    $('#listView').hide();
    $('#treeView').show();
    $('#treeViewBtn').addClass('active');
    $('#listViewBtn').removeClass('active');
    
    loadCategoryTree();
}

function showListView() {
    $('#treeView').hide();
    $('#listView').show();
    $('#listViewBtn').addClass('active');
    $('#treeViewBtn').removeClass('active');
}

function loadCategoryTree() {
    $.ajax({
        url: '{{ route("inventory.categories.tree") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderCategoryTree(response.data);
            }
        },
        error: function(xhr) {
            $('#categoryTree').html('<div class="alert alert-danger">Error loading category tree</div>');
        }
    });
}

function renderCategoryTree(categories) {
    let html = '<ul class="list-unstyled">';
    
    categories.forEach(category => {
        html += renderCategoryNode(category);
    });
    
    html += '</ul>';
    $('#categoryTree').html(html);
}

function renderCategoryNode(category) {
    let html = `
        <li class="mb-2">
            <div class="d-flex align-items-center justify-content-between p-2 border rounded">
                <div class="d-flex align-items-center">
                    ${category.icon ? `<i class="${category.icon} me-2 text-primary"></i>` : ''}
                    <strong>${category.name}</strong>
                    <span class="badge bg-info ms-2">${category.items_count || 0}</span>
                    ${!category.is_active ? '<span class="badge bg-danger ms-2">Inactive</span>' : ''}
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editCategory(${category.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-info" onclick="viewCategory(${category.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteCategory(${category.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
    `;
    
    if (category.children && category.children.length > 0) {
        html += '<ul class="list-unstyled ms-4 mt-2">';
        category.children.forEach(child => {
            html += renderCategoryNode(child);
        });
        html += '</ul>';
    }
    
    html += '</li>';
    return html;
}

function editCategory(id) {
    $.ajax({
        url: `{{ route("inventory.categories.index") }}/${id}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const category = response.data;
                
                $('#categoryModalTitle').text('Edit Category');
                $('#categoryId').val(category.id);
                $('#categoryName').val(category.name);
                $('#categoryCode').val(category.code);
                $('#parentCategory').val(category.parent_id);
                $('#categoryIcon').val(category.icon);
                $('#categoryDescription').val(category.description);
                $('#sortOrder').val(category.sort_order);
                $('#isActive').prop('checked', category.is_active);
                $('#allowAssets').prop('checked', category.allow_assets);
                $('#allowConsumables').prop('checked', category.allow_consumables);
                $('#requiresApproval').prop('checked', category.requires_approval);
                $('#trackSerial').prop('checked', category.track_serial);
                
                // Update icon preview
                $('#iconPreview').attr('class', category.icon || 'fas fa-tag');
                
                $('#categoryModal').modal('show');
            }
        },
        error: function(xhr) {
            showToast('Error loading category details', 'error');
        }
    });
}

function viewCategory(id) {
    $.ajax({
        url: `{{ route("inventory.categories.index") }}/${id}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const category = response.data;
                
                let content = `
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr><td class="fw-bold">Name:</td><td>${category.name}</td></tr>
                                <tr><td class="fw-bold">Code:</td><td>${category.code || 'Not set'}</td></tr>
                                <tr><td class="fw-bold">Parent:</td><td>${category.parent ? category.parent.name : 'None'}</td></tr>
                                <tr><td class="fw-bold">Items Count:</td><td>${category.items_count || 0}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr><td class="fw-bold">Status:</td><td>${category.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'}</td></tr>
                                <tr><td class="fw-bold">Sort Order:</td><td>${category.sort_order}</td></tr>
                                <tr><td class="fw-bold">Created:</td><td>${new Date(category.created_at).toLocaleDateString()}</td></tr>
                                <tr><td class="fw-bold">Updated:</td><td>${new Date(category.updated_at).toLocaleDateString()}</td></tr>
                            </table>
                        </div>
                    </div>
                `;
                
                if (category.description) {
                    content += `<div class="mt-3"><h6>Description:</h6><p>${category.description}</p></div>`;
                }
                
                content += `
                    <div class="mt-3">
                        <h6>Properties:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" ${category.allow_assets ? 'checked' : ''} disabled>
                                    <label class="form-check-label">Allow Assets</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" ${category.allow_consumables ? 'checked' : ''} disabled>
                                    <label class="form-check-label">Allow Consumables</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" ${category.requires_approval ? 'checked' : ''} disabled>
                                    <label class="form-check-label">Requires Approval</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" ${category.track_serial ? 'checked' : ''} disabled>
                                    <label class="form-check-label">Track Serial Numbers</label>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#categoryDetailsContent').html(content);
                $('#categoryDetailsModal').modal('show');
            }
        },
        error: function(xhr) {
            showToast('Error loading category details', 'error');
        }
    });
}

function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
        $.ajax({
            url: `{{ route("inventory.categories.index") }}/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast('Category deleted successfully', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error deleting category';
                showToast(message, 'error');
            }
        });
    }
}

function saveCategory() {
    const formData = new FormData(document.getElementById('categoryForm'));
    const categoryId = $('#categoryId').val();
    const url = categoryId ? 
        `{{ route("inventory.categories.index") }}/${categoryId}` : 
        '{{ route("inventory.categories.store") }}';
    const method = categoryId ? 'PUT' : 'POST';
    
    if (categoryId) {
        formData.append('_method', 'PUT');
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showToast('Category saved successfully', 'success');
                $('#categoryModal').modal('hide');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Error saving category';
            showToast(message, 'error');
        }
    });
}

function bulkActivate() {
    bulkAction('activate');
}

function bulkDeactivate() {
    bulkAction('deactivate');
}

function bulkDelete() {
    if (confirm('Are you sure you want to delete the selected categories?')) {
        bulkAction('delete');
    }
}

function bulkAction(action) {
    const selectedIds = $('.category-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selectedIds.length === 0) {
        showToast('No categories selected', 'warning');
        return;
    }
    
    $.ajax({
        url: '{{ route("inventory.categories.bulk-action") }}',
        method: 'POST',
        data: {
            action: action,
            ids: selectedIds
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showToast(`Categories ${action}d successfully`, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || `Error ${action}ing categories`;
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

// Reset form when modal is hidden
$('#categoryModal').on('hidden.bs.modal', function() {
    $('#categoryForm')[0].reset();
    $('#categoryModalTitle').text('Add Category');
    $('#categoryId').val('');
    $('#iconPreview').attr('class', 'fas fa-tag');
});
</script>
@endsection