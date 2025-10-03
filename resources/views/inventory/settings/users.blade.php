@extends('layouts.app')

@section('title', 'User Management Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                            <li class="breadcrumb-item active">User Management</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">User Management Settings</h1>
                    <p class="text-muted">Manage users, roles, permissions, and access control</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="exportUserData()">
                        <i class="fas fa-download me-2"></i>Export Data
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveAllUserSettings()">
                        <i class="fas fa-save me-2"></i>Save All Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="userManagementTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                        <i class="fas fa-users me-2"></i>Users
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab">
                        <i class="fas fa-user-tag me-2"></i>Roles
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions" type="button" role="tab">
                        <i class="fas fa-key me-2"></i>Permissions
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="access-control-tab" data-bs-toggle="tab" data-bs-target="#access-control" type="button" role="tab">
                        <i class="fas fa-shield-alt me-2"></i>Access Control
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="audit-logs-tab" data-bs-toggle="tab" data-bs-target="#audit-logs" type="button" role="tab">
                        <i class="fas fa-history me-2"></i>Audit Logs
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-lock me-2"></i>Security
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="userManagementTabContent">
        <!-- Users Tab -->
        <div class="tab-pane fade show active" id="users" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">User Management</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-1"></i>Add User
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- User Filters -->
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <select class="form-select" id="userStatusFilter">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="userRoleFilter">
                                        <option value="">All Roles</option>
                                        <option value="admin">Admin</option>
                                        <option value="manager">Manager</option>
                                        <option value="user">User</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="userSearchInput" placeholder="Search users...">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="applyUserFilters()">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Users Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Last Login</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usersTableBody">
                                        <!-- Users will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- User Statistics -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">User Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-primary mb-0" id="totalUsers">0</h4>
                                        <small class="text-muted">Total Users</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success mb-0" id="activeUsers">0</h4>
                                    <small class="text-muted">Active Users</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-warning mb-0" id="inactiveUsers">0</h4>
                                        <small class="text-muted">Inactive</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-danger mb-0" id="suspendedUsers">0</h4>
                                    <small class="text-muted">Suspended</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Recent User Activity</h6>
                        </div>
                        <div class="card-body">
                            <div id="recentUserActivity">
                                <!-- Recent activity will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles Tab -->
        <div class="tab-pane fade" id="roles" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Role Management</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                                <i class="fas fa-plus me-1"></i>Add Role
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Role Name</th>
                                            <th>Description</th>
                                            <th>Users</th>
                                            <th>Permissions</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rolesTableBody">
                                        <!-- Roles will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Role Distribution -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Role Distribution</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="roleDistributionChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions Tab -->
        <div class="tab-pane fade" id="permissions" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Permission Management</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPermissionModal">
                                <i class="fas fa-plus me-1"></i>Add Permission
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Permission Categories -->
                            <div class="accordion" id="permissionAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#inventoryPermissions">
                                            <i class="fas fa-boxes me-2"></i>Inventory Management
                                        </button>
                                    </h2>
                                    <div id="inventoryPermissions" class="accordion-collapse collapse show" data-bs-parent="#permissionAccordion">
                                        <div class="accordion-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Permission</th>
                                                            <th>Description</th>
                                                            <th>Admin</th>
                                                            <th>Manager</th>
                                                            <th>User</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="inventoryPermissionsTable">
                                                        <!-- Inventory permissions will be loaded here -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#userPermissions">
                                            <i class="fas fa-users me-2"></i>User Management
                                        </button>
                                    </h2>
                                    <div id="userPermissions" class="accordion-collapse collapse" data-bs-parent="#permissionAccordion">
                                        <div class="accordion-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Permission</th>
                                                            <th>Description</th>
                                                            <th>Admin</th>
                                                            <th>Manager</th>
                                                            <th>User</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="userPermissionsTable">
                                                        <!-- User permissions will be loaded here -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#systemPermissions">
                                            <i class="fas fa-cog me-2"></i>System Settings
                                        </button>
                                    </h2>
                                    <div id="systemPermissions" class="accordion-collapse collapse" data-bs-parent="#permissionAccordion">
                                        <div class="accordion-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Permission</th>
                                                            <th>Description</th>
                                                            <th>Admin</th>
                                                            <th>Manager</th>
                                                            <th>User</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="systemPermissionsTable">
                                                        <!-- System permissions will be loaded here -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Permission Summary -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Permission Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Total Permissions</span>
                                    <strong id="totalPermissions">0</strong>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Inventory</span>
                                    <span id="inventoryPermissionCount">0</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>User Management</span>
                                    <span id="userPermissionCount">0</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>System Settings</span>
                                    <span id="systemPermissionCount">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Access Control Tab -->
        <div class="tab-pane fade" id="access-control" role="tabpanel">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">IP Access Control</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Enable IP Restrictions</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enableIpRestrictions">
                                        <label class="form-check-label" for="enableIpRestrictions">
                                            Restrict access by IP address
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="allowedIps" class="form-label">Allowed IP Addresses</label>
                                    <textarea class="form-control" id="allowedIps" rows="4" placeholder="Enter IP addresses, one per line&#10;192.168.1.100&#10;10.0.0.0/24"></textarea>
                                    <div class="form-text">Enter IP addresses or CIDR ranges, one per line</div>
                                </div>

                                <div class="mb-3">
                                    <label for="blockedIps" class="form-label">Blocked IP Addresses</label>
                                    <textarea class="form-control" id="blockedIps" rows="4" placeholder="Enter IP addresses to block, one per line"></textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Session Management</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label for="sessionTimeout" class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="sessionTimeout" value="30" min="5" max="1440">
                                    <div class="form-text">Automatically log out users after inactivity</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Concurrent Sessions</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="concurrentSessions" id="allowMultiple" value="multiple" checked>
                                        <label class="form-check-label" for="allowMultiple">
                                            Allow multiple sessions per user
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="concurrentSessions" id="singleSession" value="single">
                                        <label class="form-check-label" for="singleSession">
                                            Only one session per user
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Force Password Change</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="forcePasswordChange">
                                        <label class="form-check-label" for="forcePasswordChange">
                                            Force password change on first login
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="passwordExpiry" class="form-label">Password Expiry (days)</label>
                                    <input type="number" class="form-control" id="passwordExpiry" value="90" min="0" max="365">
                                    <div class="form-text">Set to 0 to disable password expiry</div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Active Sessions</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>IP Address</th>
                                            <th>Location</th>
                                            <th>Device</th>
                                            <th>Login Time</th>
                                            <th>Last Activity</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activeSessionsTable">
                                        <!-- Active sessions will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Logs Tab -->
        <div class="tab-pane fade" id="audit-logs" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">User Audit Logs</h5>
                        </div>
                        <div class="card-body">
                            <!-- Audit Log Filters -->
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <select class="form-select" id="auditUserFilter">
                                        <option value="">All Users</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select" id="auditActionFilter">
                                        <option value="">All Actions</option>
                                        <option value="login">Login</option>
                                        <option value="logout">Logout</option>
                                        <option value="create">Create</option>
                                        <option value="update">Update</option>
                                        <option value="delete">Delete</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control" id="auditDateFrom">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control" id="auditDateTo">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="auditSearchInput" placeholder="Search logs...">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-primary w-100" onclick="applyAuditFilters()">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Audit Logs Table -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Resource</th>
                                            <th>IP Address</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="auditLogsTableBody">
                                        <!-- Audit logs will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                                </ul>
                            </nav>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" class="btn btn-outline-primary me-2" onclick="refreshAuditLogs()">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="exportAuditLogs()">
                                    <i class="fas fa-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div class="tab-pane fade" id="security" role="tabpanel">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Password Policy</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label for="minPasswordLength" class="form-label">Minimum Password Length</label>
                                    <input type="number" class="form-control" id="minPasswordLength" value="8" min="6" max="32">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password Requirements</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireUppercase" checked>
                                        <label class="form-check-label" for="requireUppercase">
                                            Require uppercase letters
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireLowercase" checked>
                                        <label class="form-check-label" for="requireLowercase">
                                            Require lowercase letters
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireNumbers" checked>
                                        <label class="form-check-label" for="requireNumbers">
                                            Require numbers
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireSpecialChars" checked>
                                        <label class="form-check-label" for="requireSpecialChars">
                                            Require special characters
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="passwordHistory" class="form-label">Password History</label>
                                    <input type="number" class="form-control" id="passwordHistory" value="5" min="0" max="24">
                                    <div class="form-text">Prevent reuse of last N passwords</div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Two-Factor Authentication</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">2FA Settings</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="twoFactorAuth" id="tfa-optional" value="optional" checked>
                                        <label class="form-check-label" for="tfa-optional">
                                            Optional for all users
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="twoFactorAuth" id="tfa-required-admin" value="required-admin">
                                        <label class="form-check-label" for="tfa-required-admin">
                                            Required for administrators
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="twoFactorAuth" id="tfa-required-all" value="required-all">
                                        <label class="form-check-label" for="tfa-required-all">
                                            Required for all users
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">2FA Methods</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tfaApp" checked>
                                        <label class="form-check-label" for="tfaApp">
                                            Authenticator App (TOTP)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tfaSms">
                                        <label class="form-check-label" for="tfaSms">
                                            SMS (Text Message)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="tfaEmail">
                                        <label class="form-check-label" for="tfaEmail">
                                            Email
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Login Security</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label for="maxLoginAttempts" class="form-label">Max Login Attempts</label>
                                    <input type="number" class="form-control" id="maxLoginAttempts" value="5" min="3" max="10">
                                    <div class="form-text">Lock account after failed attempts</div>
                                </div>

                                <div class="mb-3">
                                    <label for="lockoutDuration" class="form-label">Lockout Duration (minutes)</label>
                                    <input type="number" class="form-control" id="lockoutDuration" value="15" min="5" max="1440">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Login Notifications</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notifySuccessfulLogin">
                                        <label class="form-check-label" for="notifySuccessfulLogin">
                                            Notify on successful login
                                        </label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notifyFailedLogin" checked>
                                        <label class="form-check-label" for="notifyFailedLogin">
                                            Notify on failed login attempts
                                        </label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="notifyNewDevice" checked>
                                        <label class="form-check-label" for="notifyNewDevice">
                                            Notify on new device login
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Security Monitoring</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Failed Login Attempts (24h)</span>
                                    <span class="badge bg-warning" id="failedLoginCount">0</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Locked Accounts</span>
                                    <span class="badge bg-danger" id="lockedAccountCount">0</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Suspicious Activities</span>
                                    <span class="badge bg-info" id="suspiciousActivityCount">0</span>
                                </div>
                            </div>
                            <hr>
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-primary" onclick="generateSecurityReport()">
                                    <i class="fas fa-chart-line me-2"></i>Generate Security Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="userName" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="userName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="userEmail" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="userEmail" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="userRole" class="form-label">Role *</label>
                                <select class="form-select" id="userRole" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="manager">Manager</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="userStatus" class="form-label">Status</label>
                                <select class="form-select" id="userStatus">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="userPassword" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="userPassword" required>
                        <div class="form-text">Password will be sent to user's email</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendWelcomeEmail" checked>
                            <label class="form-check-label" for="sendWelcomeEmail">
                                Send welcome email with login credentials
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addUser()">Add User</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addRoleForm">
                    <div class="mb-3">
                        <label for="roleName" class="form-label">Role Name *</label>
                        <input type="text" class="form-control" id="roleName" required>
                    </div>
                    <div class="mb-3">
                        <label for="roleDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="roleDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Base Permissions</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="baseReadPermission" checked>
                            <label class="form-check-label" for="baseReadPermission">
                                Read Access
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="baseWritePermission">
                            <label class="form-check-label" for="baseWritePermission">
                                Write Access
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="baseDeletePermission">
                            <label class="form-check-label" for="baseDeletePermission">
                                Delete Access
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addRole()">Add Role</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Permission Modal -->
<div class="modal fade" id="addPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Permission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPermissionForm">
                    <div class="mb-3">
                        <label for="permissionName" class="form-label">Permission Name *</label>
                        <input type="text" class="form-control" id="permissionName" required>
                    </div>
                    <div class="mb-3">
                        <label for="permissionCategory" class="form-label">Category *</label>
                        <select class="form-select" id="permissionCategory" required>
                            <option value="">Select Category</option>
                            <option value="inventory">Inventory Management</option>
                            <option value="users">User Management</option>
                            <option value="system">System Settings</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="permissionDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="permissionDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addPermission()">Add Permission</button>
            </div>
        </div>
    </div>
</div>

<style>
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(45deg, #007bff, #0056b3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.permission-matrix .form-check-input {
    margin: 0;
}

.permission-matrix td {
    text-align: center;
    vertical-align: middle;
}

.session-info {
    font-size: 0.875rem;
}

.security-metric {
    padding: 1rem;
    border-radius: 0.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid #007bff;
}

.audit-log-entry {
    border-left: 3px solid #dee2e6;
    padding-left: 1rem;
}

.audit-log-entry.success {
    border-left-color: #28a745;
}

.audit-log-entry.warning {
    border-left-color: #ffc107;
}

.audit-log-entry.danger {
    border-left-color: #dc3545;
}

.role-card {
    transition: transform 0.2s;
}

.role-card:hover {
    transform: translateY(-2px);
}

.permission-category {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.user-management-tabs .nav-link {
    border-radius: 0.5rem 0.5rem 0 0;
    margin-right: 0.25rem;
}

.user-management-tabs .nav-link.active {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-color: #007bff;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.btn-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    margin: 0 0.125rem;
}

.chart-container {
    position: relative;
    height: 300px;
}

.activity-item {
    padding: 0.75rem;
    border-left: 3px solid #dee2e6;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    border-radius: 0 0.25rem 0.25rem 0;
}

.activity-item.recent {
    border-left-color: #28a745;
}

.activity-item.warning {
    border-left-color: #ffc107;
}

.activity-item.error {
    border-left-color: #dc3545;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize user management settings
    initializeUserManagement();
    
    // Load initial data
    loadUsers();
    loadRoles();
    loadPermissions();
    loadActiveSessions();
    loadAuditLogs();
    loadUserStatistics();
    loadRecentUserActivity();
    loadSecurityMetrics();
    
    // Initialize role distribution chart
    initializeRoleChart();
    
    // Set up event listeners
    setupEventListeners();
});

// Initialize user management
function initializeUserManagement() {
    // Load saved settings from localStorage
    const savedSettings = localStorage.getItem('userManagementSettings');
    if (savedSettings) {
        const settings = JSON.parse(savedSettings);
        
        // Apply saved settings to form elements
        Object.keys(settings).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                if (element.type === 'checkbox') {
                    element.checked = settings[key];
                } else {
                    element.value = settings[key];
                }
            }
        });
    }
}

// Set up event listeners
function setupEventListeners() {
    // User filters
    document.getElementById('userStatusFilter').addEventListener('change', applyUserFilters);
    document.getElementById('userRoleFilter').addEventListener('change', applyUserFilters);
    document.getElementById('userSearchInput').addEventListener('input', debounce(applyUserFilters, 300));
    
    // Audit log filters
    document.getElementById('auditUserFilter').addEventListener('change', applyAuditFilters);
    document.getElementById('auditActionFilter').addEventListener('change', applyAuditFilters);
    document.getElementById('auditDateFrom').addEventListener('change', applyAuditFilters);
    document.getElementById('auditDateTo').addEventListener('change', applyAuditFilters);
    document.getElementById('auditSearchInput').addEventListener('input', debounce(applyAuditFilters, 300));
}

// Load users
function loadUsers() {
    const users = [
        { id: 1, name: 'John Doe', email: 'john@example.com', role: 'Admin', status: 'active', lastLogin: '2024-01-15 10:30:00', avatar: 'JD' },
        { id: 2, name: 'Jane Smith', email: 'jane@example.com', role: 'Manager', status: 'active', lastLogin: '2024-01-15 09:15:00', avatar: 'JS' },
        { id: 3, name: 'Mike Johnson', email: 'mike@example.com', role: 'User', status: 'inactive', lastLogin: '2024-01-14 16:45:00', avatar: 'MJ' },
        { id: 4, name: 'Sarah Wilson', email: 'sarah@example.com', role: 'Manager', status: 'active', lastLogin: '2024-01-15 11:20:00', avatar: 'SW' },
        { id: 5, name: 'David Brown', email: 'david@example.com', role: 'User', status: 'suspended', lastLogin: '2024-01-13 14:30:00', avatar: 'DB' }
    ];
    
    const tableBody = document.getElementById('usersTableBody');
    if (tableBody) {
        tableBody.innerHTML = users.map(user => `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-3">${user.avatar}</div>
                        <div>
                            <div class="fw-bold">${user.name}</div>
                            <small class="text-muted">${user.email}</small>
                        </div>
                    </div>
                </td>
                <td><span class="badge bg-primary">${user.role}</span></td>
                <td>
                    <span class="badge status-badge bg-${user.status === 'active' ? 'success' : user.status === 'inactive' ? 'warning' : 'danger'}">
                        ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                    </span>
                </td>
                <td><small>${user.lastLogin}</small></td>
                <td>
                    <button class="btn btn-outline-primary btn-action" onclick="editUser(${user.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-info btn-action" onclick="viewUserDetails(${user.id})" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-warning btn-action" onclick="resetUserPassword(${user.id})" title="Reset Password">
                        <i class="fas fa-key"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-action" onclick="deleteUser(${user.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
}

// Load roles
function loadRoles() {
    const roles = [
        { id: 1, name: 'Administrator', description: 'Full system access', users: 2, permissions: 25 },
        { id: 2, name: 'Manager', description: 'Department management access', users: 5, permissions: 15 },
        { id: 3, name: 'User', description: 'Basic user access', users: 12, permissions: 8 },
        { id: 4, name: 'Viewer', description: 'Read-only access', users: 3, permissions: 3 }
    ];
    
    const tableBody = document.getElementById('rolesTableBody');
    if (tableBody) {
        tableBody.innerHTML = roles.map(role => `
            <tr>
                <td>
                    <div class="fw-bold">${role.name}</div>
                </td>
                <td><small class="text-muted">${role.description}</small></td>
                <td><span class="badge bg-info">${role.users}</span></td>
                <td><span class="badge bg-success">${role.permissions}</span></td>
                <td>
                    <button class="btn btn-outline-primary btn-action" onclick="editRole(${role.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-info btn-action" onclick="viewRolePermissions(${role.id})" title="Permissions">
                        <i class="fas fa-key"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-action" onclick="deleteRole(${role.id})" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
}

// Load permissions
function loadPermissions() {
    const inventoryPermissions = [
        { name: 'inventory.view', description: 'View inventory items', admin: true, manager: true, user: true },
        { name: 'inventory.create', description: 'Create new inventory items', admin: true, manager: true, user: false },
        { name: 'inventory.edit', description: 'Edit inventory items', admin: true, manager: true, user: false },
        { name: 'inventory.delete', description: 'Delete inventory items', admin: true, manager: false, user: false },
        { name: 'inventory.export', description: 'Export inventory data', admin: true, manager: true, user: false }
    ];
    
    const userPermissions = [
        { name: 'users.view', description: 'View user list', admin: true, manager: true, user: false },
        { name: 'users.create', description: 'Create new users', admin: true, manager: false, user: false },
        { name: 'users.edit', description: 'Edit user details', admin: true, manager: false, user: false },
        { name: 'users.delete', description: 'Delete users', admin: true, manager: false, user: false }
    ];
    
    const systemPermissions = [
        { name: 'settings.view', description: 'View system settings', admin: true, manager: false, user: false },
        { name: 'settings.edit', description: 'Edit system settings', admin: true, manager: false, user: false },
        { name: 'backup.create', description: 'Create system backups', admin: true, manager: false, user: false },
        { name: 'logs.view', description: 'View system logs', admin: true, manager: false, user: false }
    ];
    
    loadPermissionTable('inventoryPermissionsTable', inventoryPermissions);
    loadPermissionTable('userPermissionsTable', userPermissions);
    loadPermissionTable('systemPermissionsTable', systemPermissions);
    
    // Update permission counts
    document.getElementById('inventoryPermissionCount').textContent = inventoryPermissions.length;
    document.getElementById('userPermissionCount').textContent = userPermissions.length;
    document.getElementById('systemPermissionCount').textContent = systemPermissions.length;
    document.getElementById('totalPermissions').textContent = inventoryPermissions.length + userPermissions.length + systemPermissions.length;
}

// Load permission table
function loadPermissionTable(tableId, permissions) {
    const tableBody = document.getElementById(tableId);
    if (tableBody) {
        tableBody.innerHTML = permissions.map((permission, index) => `
            <tr>
                <td><code>${permission.name}</code></td>
                <td><small>${permission.description}</small></td>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input" ${permission.admin ? 'checked' : ''} 
                           onchange="updatePermission('${permission.name}', 'admin', this.checked)">
                </td>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input" ${permission.manager ? 'checked' : ''} 
                           onchange="updatePermission('${permission.name}', 'manager', this.checked)">
                </td>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input" ${permission.user ? 'checked' : ''} 
                           onchange="updatePermission('${permission.name}', 'user', this.checked)">
                </td>
            </tr>
        `).join('');
    }
}

// Load active sessions
function loadActiveSessions() {
    const sessions = [
        { user: 'John Doe', ip: '192.168.1.100', location: 'New York, US', device: 'Chrome on Windows', loginTime: '2024-01-15 08:30:00', lastActivity: '2 minutes ago' },
        { user: 'Jane Smith', ip: '10.0.0.50', location: 'London, UK', device: 'Safari on macOS', loginTime: '2024-01-15 09:15:00', lastActivity: '5 minutes ago' },
        { user: 'Mike Johnson', ip: '172.16.0.25', location: 'Tokyo, JP', device: 'Firefox on Linux', loginTime: '2024-01-15 10:00:00', lastActivity: '1 hour ago' }
    ];
    
    const tableBody = document.getElementById('activeSessionsTable');
    if (tableBody) {
        tableBody.innerHTML = sessions.map((session, index) => `
            <tr>
                <td>${session.user}</td>
                <td><code>${session.ip}</code></td>
                <td><small>${session.location}</small></td>
                <td><small>${session.device}</small></td>
                <td><small>${session.loginTime}</small></td>
                <td><small>${session.lastActivity}</small></td>
                <td>
                    <button class="btn btn-outline-warning btn-action" onclick="terminateSession(${index})" title="Terminate">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
}

// Load audit logs
function loadAuditLogs() {
    const logs = [
        { timestamp: '2024-01-15 14:30:25', user: 'John Doe', action: 'login', resource: 'System', ip: '192.168.1.100', details: 'Successful login' },
        { timestamp: '2024-01-15 14:25:10', user: 'Jane Smith', action: 'update', resource: 'User Profile', ip: '10.0.0.50', details: 'Updated profile information' },
        { timestamp: '2024-01-15 14:20:33', user: 'Admin', action: 'create', resource: 'User Account', ip: '192.168.1.1', details: 'Created new user: Mike Johnson' },
        { timestamp: '2024-01-15 14:15:17', user: 'Mike Johnson', action: 'delete', resource: 'Inventory Item', ip: '172.16.0.25', details: 'Deleted item: Widget ABC' },
        { timestamp: '2024-01-15 14:10:00', user: 'Sarah Wilson', action: 'logout', resource: 'System', ip: '10.0.0.75', details: 'User logged out' }
    ];
    
    const tableBody = document.getElementById('auditLogsTableBody');
    if (tableBody) {
        tableBody.innerHTML = logs.map(log => `
            <tr>
                <td><small>${log.timestamp}</small></td>
                <td>${log.user}</td>
                <td>
                    <span class="badge bg-${log.action === 'login' ? 'success' : log.action === 'logout' ? 'info' : log.action === 'delete' ? 'danger' : 'primary'}">
                        ${log.action.toUpperCase()}
                    </span>
                </td>
                <td>${log.resource}</td>
                <td><code>${log.ip}</code></td>
                <td>
                    <button class="btn btn-outline-info btn-action" onclick="viewAuditDetails('${log.timestamp}')" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    // Populate user filter dropdown
    const userFilter = document.getElementById('auditUserFilter');
    if (userFilter) {
        const uniqueUsers = [...new Set(logs.map(log => log.user))];
        userFilter.innerHTML = '<option value="">All Users</option>' + 
            uniqueUsers.map(user => `<option value="${user}">${user}</option>`).join('');
    }
}

// Load user statistics
function loadUserStatistics() {
    // Simulate loading statistics
    document.getElementById('totalUsers').textContent = '22';
    document.getElementById('activeUsers').textContent = '18';
    document.getElementById('inactiveUsers').textContent = '3';
    document.getElementById('suspendedUsers').textContent = '1';
}

// Load recent user activity
function loadRecentUserActivity() {
    const activities = [
        { user: 'John Doe', action: 'Logged in', time: '2 minutes ago', type: 'recent' },
        { user: 'Jane Smith', action: 'Updated profile', time: '5 minutes ago', type: 'recent' },
        { user: 'Mike Johnson', action: 'Failed login attempt', time: '10 minutes ago', type: 'warning' },
        { user: 'Sarah Wilson', action: 'Password reset', time: '1 hour ago', type: 'recent' }
    ];
    
    const container = document.getElementById('recentUserActivity');
    if (container) {
        container.innerHTML = activities.map(activity => `
            <div class="activity-item ${activity.type}">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>${activity.user}</strong>
                        <div><small>${activity.action}</small></div>
                    </div>
                    <small class="text-muted">${activity.time}</small>
                </div>
            </div>
        `).join('');
    }
}

// Load security metrics
function loadSecurityMetrics() {
    document.getElementById('failedLoginCount').textContent = '12';
    document.getElementById('lockedAccountCount').textContent = '2';
    document.getElementById('suspiciousActivityCount').textContent = '5';
}

// Initialize role distribution chart
function initializeRoleChart() {
    const ctx = document.getElementById('roleDistributionChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Admin', 'Manager', 'User', 'Viewer'],
                datasets: [{
                    data: [2, 5, 12, 3],
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#28a745',
                        '#6c757d'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// User management functions
function addUser() {
    const form = document.getElementById('addUserForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Simulate API call
    showNotification('User added successfully!', 'success');
    
    // Close modal and refresh users
    const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
    modal.hide();
    form.reset();
    loadUsers();
}

function editUser(userId) {
    // Simulate loading user data and opening edit modal
    showNotification('Edit user functionality would be implemented here', 'info');
}

function viewUserDetails(userId) {
    // Simulate viewing user details
    showNotification('View user details functionality would be implemented here', 'info');
}

function resetUserPassword(userId) {
    if (confirm('Are you sure you want to reset this user\'s password?')) {
        showNotification('Password reset email sent successfully!', 'success');
    }
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        showNotification('User deleted successfully!', 'success');
        loadUsers();
    }
}

// Role management functions
function addRole() {
    const form = document.getElementById('addRoleForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    showNotification('Role added successfully!', 'success');
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('addRoleModal'));
    modal.hide();
    form.reset();
    loadRoles();
}

function editRole(roleId) {
    showNotification('Edit role functionality would be implemented here', 'info');
}

function viewRolePermissions(roleId) {
    showNotification('View role permissions functionality would be implemented here', 'info');
}

function deleteRole(roleId) {
    if (confirm('Are you sure you want to delete this role?')) {
        showNotification('Role deleted successfully!', 'success');
        loadRoles();
    }
}

// Permission management functions
function addPermission() {
    const form = document.getElementById('addPermissionForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    showNotification('Permission added successfully!', 'success');
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('addPermissionModal'));
    modal.hide();
    form.reset();
    loadPermissions();
}

function updatePermission(permissionName, role, hasPermission) {
    // Simulate updating permission
    console.log(`Updating ${permissionName} for ${role}: ${hasPermission}`);
    showNotification(`Permission updated for ${role}`, 'success');
}

// Filter functions
function applyUserFilters() {
    const statusFilter = document.getElementById('userStatusFilter').value;
    const roleFilter = document.getElementById('userRoleFilter').value;
    const searchInput = document.getElementById('userSearchInput').value;
    
    // Simulate filtering - in real implementation, this would make an API call
    console.log('Applying user filters:', { statusFilter, roleFilter, searchInput });
    loadUsers(); // Reload with filters
}

function applyAuditFilters() {
    const userFilter = document.getElementById('auditUserFilter').value;
    const actionFilter = document.getElementById('auditActionFilter').value;
    const dateFrom = document.getElementById('auditDateFrom').value;
    const dateTo = document.getElementById('auditDateTo').value;
    const searchInput = document.getElementById('auditSearchInput').value;
    
    console.log('Applying audit filters:', { userFilter, actionFilter, dateFrom, dateTo, searchInput });
    loadAuditLogs(); // Reload with filters
}

// Session management functions
function terminateSession(sessionIndex) {
    if (confirm('Are you sure you want to terminate this session?')) {
        showNotification('Session terminated successfully!', 'success');
        loadActiveSessions();
    }
}

// Audit log functions
function viewAuditDetails(timestamp) {
    showNotification('View audit details functionality would be implemented here', 'info');
}

function refreshAuditLogs() {
    showNotification('Refreshing audit logs...', 'info');
    loadAuditLogs();
}

function exportAuditLogs() {
    showNotification('Exporting audit logs...', 'info');
    // Simulate export functionality
}

// Security functions
function generateSecurityReport() {
    showNotification('Generating security report...', 'info');
    // Simulate report generation
}

// Data export functions
function exportUserData() {
    showNotification('Exporting user data...', 'info');
    // Simulate data export
}

// Save all settings
function saveAllUserSettings() {
    // Collect all form data
    const settings = {};
    
    // Collect settings from all forms
    const formElements = document.querySelectorAll('#userManagementTabContent input, #userManagementTabContent select, #userManagementTabContent textarea');
    formElements.forEach(element => {
        if (element.id) {
            if (element.type === 'checkbox') {
                settings[element.id] = element.checked;
            } else {
                settings[element.id] = element.value;
            }
        }
    });
    
    // Save to localStorage (in real app, this would be an API call)
    localStorage.setItem('userManagementSettings', JSON.stringify(settings));
    
    showNotification('All user management settings saved successfully!', 'success');
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

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Call initialize tooltips when DOM is ready
document.addEventListener('DOMContentLoaded', initializeTooltips);
</script>
@endsection