@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users mr-2"></i>User Management
            </h1>
            <p class="mb-0 text-muted">Manage system users, roles, and permissions</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="refreshUsers()">
                <i class="fas fa-sync-alt mr-1"></i>Refresh
            </button>
            <button class="btn btn-success" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-user-plus mr-1"></i>Add User
            </button>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter mr-2"></i>Filters & Search
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="searchUsers" class="form-label">Search Users</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchUsers" placeholder="Name, email, or ID...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" onclick="searchUsers()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="filterRole" class="form-label">Role</label>
                    <select class="form-control" id="filterRole" onchange="applyFilters()">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="employee">Employee</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filterStatus" class="form-label">Status</label>
                    <select class="form-control" id="filterStatus" onchange="applyFilters()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filterDepartment" class="form-label">Department</label>
                    <select class="form-control" id="filterDepartment" onchange="applyFilters()">
                        <option value="">All Departments</option>
                        <!-- Departments will be loaded dynamically -->
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Actions</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-secondary btn-sm" onclick="resetFilters()">
                            <i class="fas fa-undo mr-1"></i>Reset
                        </button>
                        <button class="btn btn-info btn-sm" onclick="exportUsers()">
                            <i class="fas fa-download mr-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalUsers">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeUsers">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Online Now</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="onlineUsers">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-circle fa-2x text-success"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">New This Month</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="newUsers">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table mr-2"></i>Users List
            </h6>
            <div class="d-flex align-items-center">
                <span class="text-muted mr-3">Show:</span>
                <select class="form-control form-control-sm" id="perPage" onchange="changePerPage()" style="width: auto;">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="text-muted ml-2">entries</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="usersTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th width="10%">Avatar</th>
                            <th width="15%">Name</th>
                            <th width="15%">Email</th>
                            <th width="10%">Role</th>
                            <th width="10%">Department</th>
                            <th width="8%">Status</th>
                            <th width="12%">Last Login</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="spinner-border" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <div class="mt-2">Loading users...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    <span id="paginationInfo">Showing 0 to 0 of 0 entries</span>
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="pagination">
                        <!-- Pagination will be generated dynamically -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card shadow mb-4" id="bulkActionsCard" style="display: none;">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <span class="mr-3">
                    <strong id="selectedCount">0</strong> users selected
                </span>
                <div class="btn-group">
                    <button class="btn btn-sm btn-success" onclick="bulkActivate()">
                        <i class="fas fa-check mr-1"></i>Activate
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="bulkDeactivate()">
                        <i class="fas fa-pause mr-1"></i>Deactivate
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkDelete()">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                    <button class="btn btn-sm btn-info" onclick="bulkExport()">
                        <i class="fas fa-download mr-1"></i>Export Selected
                    </button>
                </div>
                <button class="btn btn-sm btn-secondary ml-auto" onclick="clearSelection()">
                    <i class="fas fa-times mr-1"></i>Clear Selection
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus mr-2"></i>Add New User
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addUserForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="firstName">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="firstName" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lastName">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="lastName" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Role <span class="text-danger">*</span></label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="employee">Employee</option>
                                    <option value="viewer">Viewer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="department">Department</label>
                                <select class="form-control" id="department" name="department_id">
                                    <option value="">Select Department</option>
                                    <!-- Departments will be loaded dynamically -->
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           required
                                           minlength="8"
                                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                           title="Password must be at least 8 characters with uppercase, lowercase, number and special character">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Minimum 8 characters with uppercase, lowercase, number and special character</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="avatar">Profile Picture</label>
                        <input type="file" class="form-control-file" id="avatar" name="avatar" accept="image/*">
                        <small class="form-text text-muted">Optional. Max size: 2MB. Formats: JPG, PNG, GIF</small>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="sendWelcomeEmail" name="send_welcome_email" checked>
                            <label class="custom-control-label" for="sendWelcomeEmail">
                                Send welcome email with login credentials
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="isActive" name="is_active" checked>
                            <label class="custom-control-label" for="isActive">
                                Activate user account immediately
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus mr-1"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user mr-2"></i>User Details
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- User details will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="editUser()">
                    <i class="fas fa-edit mr-1"></i>Edit User
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        loadUsers();
        loadDepartments();
        loadUserStats();
        
        // Auto-refresh every 5 minutes
        setInterval(loadUserStats, 300000);
        
        // Search on Enter key
        $('#searchUsers').on('keypress', function(e) {
            if (e.which === 13) {
                searchUsers();
            }
        });
    });

    let currentPage = 1;
    let perPage = 25;
    let currentFilters = {};
    let selectedUsers = [];

    function loadUsers(page = 1) {
        currentPage = page;
        
        const params = {
            page: page,
            per_page: perPage,
            ...currentFilters
        };

        $.get('/api/inventory/users', params)
            .done(function(data) {
                renderUsersTable(data.data);
                updatePagination(data);
                updatePaginationInfo(data);
            })
            .fail(function() {
                $('#usersTableBody').html(`
                    <tr>
                        <td colspan="9" class="text-center text-danger py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <div>Failed to load users</div>
                        </td>
                    </tr>
                `);
            });
    }

    function renderUsersTable(users) {
        if (users.length === 0) {
            $('#usersTableBody').html(`
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <div>No users found</div>
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        users.forEach(user => {
            const isSelected = selectedUsers.includes(user.id);
            html += `
                <tr class="${isSelected ? 'table-active' : ''}">
                    <td>
                        <input type="checkbox" class="user-checkbox" value="${user.id}" 
                               ${isSelected ? 'checked' : ''} onchange="toggleUserSelection(${user.id})">
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${user.avatar ? 
                                `<img src="${user.avatar}" class="rounded-circle" width="40" height="40" alt="${user.name}">` :
                                `<div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px;">
                                    ${user.first_name.charAt(0)}${user.last_name.charAt(0)}
                                </div>`
                            }
                        </div>
                    </td>
                    <td>
                        <div class="font-weight-bold">${user.first_name} ${user.last_name}</div>
                        <small class="text-muted">ID: ${user.id}</small>
                    </td>
                    <td>
                        <div>${user.email}</div>
                        ${user.phone ? `<small class="text-muted">${user.phone}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge badge-${getRoleBadgeClass(user.role)}">${user.role}</span>
                    </td>
                    <td>
                        ${user.department ? user.department.name : '<span class="text-muted">-</span>'}
                    </td>
                    <td>
                        <span class="badge badge-${getStatusBadgeClass(user.status)}">${user.status}</span>
                        ${user.is_online ? '<i class="fas fa-circle text-success ml-1" title="Online"></i>' : ''}
                    </td>
                    <td>
                        ${user.last_login ? formatDateTime(user.last_login) : '<span class="text-muted">Never</span>'}
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewUser(${user.id})" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="editUser(${user.id})" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" onclick="resetPassword(${user.id})">
                                        <i class="fas fa-key mr-2"></i>Reset Password
                                    </a>
                                    <a class="dropdown-item" href="#" onclick="toggleUserStatus(${user.id})">
                                        <i class="fas fa-${user.status === 'active' ? 'pause' : 'play'} mr-2"></i>
                                        ${user.status === 'active' ? 'Deactivate' : 'Activate'}
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteUser(${user.id})">
                                        <i class="fas fa-trash mr-2"></i>Delete User
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });
        $('#usersTableBody').html(html);
    }

    function loadUserStats() {
        $.get('/api/inventory/users/stats')
            .done(function(data) {
                $('#totalUsers').html(`<strong>${data.total || 0}</strong>`);
                $('#activeUsers').html(`<strong>${data.active || 0}</strong>`);
                $('#onlineUsers').html(`<strong>${data.online || 0}</strong>`);
                $('#newUsers').html(`<strong>${data.new_this_month || 0}</strong>`);
            })
            .fail(function() {
                console.error('Failed to load user statistics');
            });
    }

    function loadDepartments() {
        $.get('/api/inventory/departments')
            .done(function(data) {
                let options = '<option value="">All Departments</option>';
                data.forEach(dept => {
                    options += `<option value="${dept.id}">${dept.name}</option>`;
                });
                $('#filterDepartment, #department').html(options);
            })
            .fail(function() {
                console.error('Failed to load departments');
            });
    }

    function searchUsers() {
        currentFilters.search = $('#searchUsers').val();
        loadUsers(1);
    }

    function applyFilters() {
        currentFilters = {
            search: $('#searchUsers').val(),
            role: $('#filterRole').val(),
            status: $('#filterStatus').val(),
            department: $('#filterDepartment').val()
        };
        loadUsers(1);
    }

    function resetFilters() {
        $('#searchUsers, #filterRole, #filterStatus, #filterDepartment').val('');
        currentFilters = {};
        loadUsers(1);
    }

    function changePerPage() {
        perPage = parseInt($('#perPage').val());
        loadUsers(1);
    }

    function refreshUsers() {
        loadUsers(currentPage);
        loadUserStats();
        showToast('Users refreshed successfully!', 'success');
    }

    // User selection functions
    function toggleSelectAll() {
        const isChecked = $('#selectAll').is(':checked');
        $('.user-checkbox').prop('checked', isChecked);
        
        if (isChecked) {
            selectedUsers = $('.user-checkbox').map(function() {
                return parseInt($(this).val());
            }).get();
        } else {
            selectedUsers = [];
        }
        
        updateBulkActions();
    }

    function toggleUserSelection(userId) {
        const index = selectedUsers.indexOf(userId);
        if (index > -1) {
            selectedUsers.splice(index, 1);
        } else {
            selectedUsers.push(userId);
        }
        
        updateBulkActions();
        updateSelectAllCheckbox();
    }

    function updateBulkActions() {
        const count = selectedUsers.length;
        $('#selectedCount').text(count);
        
        if (count > 0) {
            $('#bulkActionsCard').show();
        } else {
            $('#bulkActionsCard').hide();
        }
    }

    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('.user-checkbox').length;
        const checkedCheckboxes = $('.user-checkbox:checked').length;
        
        $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
    }

    function clearSelection() {
        selectedUsers = [];
        $('.user-checkbox, #selectAll').prop('checked', false);
        $('#selectAll').prop('indeterminate', false);
        updateBulkActions();
    }

    // User management functions
    function viewUser(userId) {
        $.get(`/api/inventory/users/${userId}`)
            .done(function(user) {
                renderUserDetails(user);
                $('#userDetailsModal').modal('show');
            })
            .fail(function() {
                showToast('Failed to load user details', 'error');
            });
    }

    function renderUserDetails(user) {
        const html = `
            <div class="row">
                <div class="col-md-4 text-center">
                    ${user.avatar ? 
                        `<img src="${user.avatar}" class="rounded-circle mb-3" width="120" height="120" alt="${user.name}">` :
                        `<div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white mx-auto mb-3" style="width: 120px; height: 120px; font-size: 2rem;">
                            ${user.first_name.charAt(0)}${user.last_name.charAt(0)}
                        </div>`
                    }
                    <h5>${user.first_name} ${user.last_name}</h5>
                    <p class="text-muted">${user.email}</p>
                    <span class="badge badge-${getStatusBadgeClass(user.status)} badge-lg">${user.status}</span>
                </div>
                <div class="col-md-8">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>User ID:</strong></td>
                            <td>${user.id}</td>
                        </tr>
                        <tr>
                            <td><strong>Role:</strong></td>
                            <td><span class="badge badge-${getRoleBadgeClass(user.role)}">${user.role}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Department:</strong></td>
                            <td>${user.department ? user.department.name : '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>${user.phone || '-'}</td>
                        </tr>
                        <tr>
                            <td><strong>Created:</strong></td>
                            <td>${formatDateTime(user.created_at)}</td>
                        </tr>
                        <tr>
                            <td><strong>Last Login:</strong></td>
                            <td>${user.last_login ? formatDateTime(user.last_login) : 'Never'}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                ${user.is_online ? '<i class="fas fa-circle text-success mr-1"></i>Online' : '<i class="fas fa-circle text-muted mr-1"></i>Offline'}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        $('#userDetailsContent').html(html);
    }

    // Add User Form Submission
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '/api/inventory/users',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#addUserModal').modal('hide');
                $('#addUserForm')[0].reset();
                loadUsers(currentPage);
                loadUserStats();
                showToast('User created successfully!', 'success');
            },
            error: function(xhr) {
                let errorMessage = 'Failed to create user';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showToast(errorMessage, 'error');
            }
        });
    });

    // Utility Functions
    function getRoleBadgeClass(role) {
        const classes = {
            'admin': 'danger',
            'manager': 'warning',
            'employee': 'primary',
            'viewer': 'secondary'
        };
        return classes[role] || 'secondary';
    }

    function getStatusBadgeClass(status) {
        const classes = {
            'active': 'success',
            'inactive': 'secondary',
            'suspended': 'danger'
        };
        return classes[status] || 'secondary';
    }

    function formatDateTime(dateString) {
        return new Date(dateString).toLocaleString('en-IN');
    }

    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.nextElementSibling.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    function updatePagination(data) {
        // Pagination implementation
        let html = '';
        const totalPages = data.last_page;
        const currentPage = data.current_page;
        
        // Previous button
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadUsers(${currentPage - 1})">Previous</a>
        </li>`;
        
        // Page numbers
        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadUsers(${i})">${i}</a>
            </li>`;
        }
        
        // Next button
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadUsers(${currentPage + 1})">Next</a>
        </li>`;
        
        $('#pagination').html(html);
    }

    function updatePaginationInfo(data) {
        const from = data.from || 0;
        const to = data.to || 0;
        const total = data.total || 0;
        $('#paginationInfo').text(`Showing ${from} to ${to} of ${total} entries`);
    }

    function showToast(message, type = 'info') {
        const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
        const toast = `
            <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        if (!$('#toastContainer').length) {
            $('body').append('<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3"></div>');
        }
        
        const $toast = $(toast);
        $('#toastContainer').append($toast);
        
        $toast.toast('show');
        setTimeout(() => $toast.remove(), 3000);
    }
</script>
@endsection