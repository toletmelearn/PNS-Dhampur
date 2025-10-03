@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">User Details</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user mr-2"></i>User Details
            </h1>
            <p class="mb-0 text-muted">Comprehensive user information and activity</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.users.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Back to Users
            </a>
            <button type="button" class="btn btn-primary" onclick="editUser()">
                <i class="fas fa-edit mr-1"></i>Edit User
            </button>
            <div class="dropdown">
                <button class="btn btn-info dropdown-toggle" type="button" data-toggle="dropdown">
                    <i class="fas fa-cog mr-1"></i>Actions
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" onclick="resetPassword()">
                        <i class="fas fa-key mr-2"></i>Reset Password
                    </a>
                    <a class="dropdown-item" href="#" onclick="toggleUserStatus()">
                        <i class="fas fa-user-slash mr-2"></i>Toggle Status
                    </a>
                    <a class="dropdown-item" href="#" onclick="sendWelcomeEmail()">
                        <i class="fas fa-envelope mr-2"></i>Send Welcome Email
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="#" onclick="deleteUser()">
                        <i class="fas fa-trash mr-2"></i>Delete User
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading user information...</p>
    </div>

    <!-- Main Content -->
    <div id="mainContent" style="display: none;">
        <div class="row">
            <!-- User Profile Card -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-body text-center">
                        <div id="userAvatar" class="mb-3">
                            <!-- Avatar will be loaded here -->
                        </div>
                        <h4 id="userName" class="mb-1">Loading...</h4>
                        <p id="userEmail" class="text-muted mb-2">Loading...</p>
                        <span id="userStatus" class="badge badge-secondary mb-3">Loading...</span>
                        
                        <div class="row text-center mt-3">
                            <div class="col-4">
                                <h6 id="daysActive" class="text-primary mb-0">-</h6>
                                <small class="text-muted">Days Active</small>
                            </div>
                            <div class="col-4">
                                <h6 id="totalLogins" class="text-info mb-0">-</h6>
                                <small class="text-muted">Total Logins</small>
                            </div>
                            <div class="col-4">
                                <h6 id="lastSeen" class="text-success mb-0">-</h6>
                                <small class="text-muted">Last Seen</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar mr-2"></i>Quick Stats
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="quickStats">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Loading stats...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-user-shield mr-2"></i>Role & Permissions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="roleInfo">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm text-info" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Loading role info...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Information -->
            <div class="col-lg-8">
                <!-- Personal Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-user mr-2"></i>Personal Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="personalInfo">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Loading personal information...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="fas fa-briefcase mr-2"></i>Professional Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="professionalInfo">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm text-success" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Loading professional information...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-warning">
                            <i class="fas fa-cog mr-2"></i>System Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="systemInfo">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm text-warning" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Loading system information...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-history mr-2"></i>Activity Timeline
                        </h6>
                        <button class="btn btn-sm btn-outline-info" onclick="refreshActivity()">
                            <i class="fas fa-sync-alt mr-1"></i>Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="activityTimeline">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm text-info" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Loading activity timeline...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Sessions -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-secondary">
                            <i class="fas fa-desktop mr-2"></i>Active Sessions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="userSessions">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm text-secondary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Loading sessions...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key mr-2"></i>Reset Password
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="resetPasswordForm">
                    @csrf
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="newPassword" name="password" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Minimum 8 characters with letters and numbers</small>
                    </div>
                    <div class="form-group">
                        <label for="confirmNewPassword">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmNewPassword" name="password_confirmation" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmNewPassword')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="forcePasswordChange" name="force_change">
                            <label class="custom-control-label" for="forcePasswordChange">
                                Force password change on next login
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="sendPasswordEmail" name="send_email" checked>
                            <label class="custom-control-label" for="sendPasswordEmail">
                                Send new password via email
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitPasswordReset()">
                    <i class="fas fa-key mr-1"></i>Reset Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Delete User
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>
                <p>Are you sure you want to delete this user? This will:</p>
                <ul>
                    <li>Permanently remove the user account</li>
                    <li>Remove all associated data and permissions</li>
                    <li>Transfer or remove any assigned inventory items</li>
                    <li>Archive all activity logs</li>
                </ul>
                <div class="form-group mt-3">
                    <label for="deleteConfirmation">Type <strong>DELETE</strong> to confirm:</label>
                    <input type="text" class="form-control" id="deleteConfirmation" placeholder="Type DELETE">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDeleteUser()" disabled>
                    <i class="fas fa-trash mr-1"></i>Delete User
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentUser = null;
    let currentUserId = null;

    $(document).ready(function() {
        // Get user ID from URL
        const urlParts = window.location.pathname.split('/');
        currentUserId = urlParts[urlParts.length - 1];
        
        if (currentUserId) {
            loadUserData(currentUserId);
        } else {
            showToast('Invalid user ID', 'error');
            window.location.href = '{{ route("inventory.users.index") }}';
        }
        
        // Delete confirmation input handler
        $('#deleteConfirmation').on('input', function() {
            const value = $(this).val();
            $('#confirmDeleteBtn').prop('disabled', value !== 'DELETE');
        });
    });

    function loadUserData(userId) {
        $.get(`/api/inventory/users/${userId}/details`)
            .done(function(user) {
                currentUser = user;
                populateUserProfile(user);
                loadPersonalInfo(user);
                loadProfessionalInfo(user);
                loadSystemInfo(user);
                loadRoleInfo(user.role);
                loadQuickStats(userId);
                loadActivityTimeline(userId);
                loadUserSessions(userId);
                
                $('#loadingState').hide();
                $('#mainContent').show();
            })
            .fail(function(xhr) {
                let errorMessage = 'Failed to load user data';
                if (xhr.status === 404) {
                    errorMessage = 'User not found';
                }
                showToast(errorMessage, 'error');
                setTimeout(() => {
                    window.location.href = '{{ route("inventory.users.index") }}';
                }, 2000);
            });
    }

    function populateUserProfile(user) {
        // Avatar
        if (user.avatar) {
            $('#userAvatar').html(`
                <img src="${user.avatar}" alt="Avatar" class="rounded-circle img-fluid" style="width: 120px; height: 120px; object-fit: cover;">
            `);
        } else {
            $('#userAvatar').html(`
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px;">
                    <i class="fas fa-user fa-3x text-muted"></i>
                </div>
            `);
        }
        
        // Basic info
        $('#userName').text(`${user.first_name} ${user.last_name}`);
        $('#userEmail').text(user.email);
        
        // Status badge
        const statusClass = getStatusBadgeClass(user.status);
        $('#userStatus').removeClass('badge-secondary badge-success badge-warning badge-danger')
                        .addClass(`badge-${statusClass}`)
                        .text(user.status.toUpperCase());
        
        // Stats
        const createdDate = new Date(user.created_at);
        const daysSinceCreated = Math.floor((new Date() - createdDate) / (1000 * 60 * 60 * 24));
        
        $('#daysActive').text(daysSinceCreated);
        $('#totalLogins').text(user.login_count || 0);
        
        if (user.last_login_at) {
            const lastLogin = new Date(user.last_login_at);
            const hoursAgo = Math.floor((new Date() - lastLogin) / (1000 * 60 * 60));
            $('#lastSeen').text(hoursAgo < 24 ? `${hoursAgo}h` : `${Math.floor(hoursAgo / 24)}d`);
        } else {
            $('#lastSeen').text('Never');
        }
    }

    function loadPersonalInfo(user) {
        const personalHtml = `
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold text-muted">First Name:</td>
                            <td>${user.first_name}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Last Name:</td>
                            <td>${user.last_name}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Email:</td>
                            <td>${user.email}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Phone:</td>
                            <td>${user.phone || 'Not provided'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold text-muted">Date of Birth:</td>
                            <td>${user.date_of_birth || 'Not provided'}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Gender:</td>
                            <td>${user.gender ? user.gender.charAt(0).toUpperCase() + user.gender.slice(1) : 'Not specified'}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Address:</td>
                            <td>${user.address || 'Not provided'}</td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        
        $('#personalInfo').html(personalHtml);
    }

    function loadProfessionalInfo(user) {
        const professionalHtml = `
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold text-muted">Employee ID:</td>
                            <td>${user.employee_id || 'Not assigned'}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Designation:</td>
                            <td>${user.designation || 'Not specified'}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Department:</td>
                            <td>${user.department ? user.department.name : 'Not assigned'}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Reporting Manager:</td>
                            <td>${user.manager ? `${user.manager.first_name} ${user.manager.last_name}` : 'Not assigned'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold text-muted">Joining Date:</td>
                            <td>${user.joining_date || 'Not specified'}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Salary:</td>
                            <td>${user.salary ? `₹${parseFloat(user.salary).toLocaleString()}` : 'Not disclosed'}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Employment Status:</td>
                            <td>
                                <span class="badge badge-${getStatusBadgeClass(user.status)}">${user.status.toUpperCase()}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        
        $('#professionalInfo').html(professionalHtml);
    }

    function loadSystemInfo(user) {
        const systemHtml = `
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold text-muted">Role:</td>
                            <td><span class="badge badge-${getRoleBadgeClass(user.role)}">${user.role.toUpperCase()}</span></td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Account Created:</td>
                            <td>${formatDateTime(user.created_at)}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Last Updated:</td>
                            <td>${formatDateTime(user.updated_at)}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Last Login:</td>
                            <td>${user.last_login_at ? formatDateTime(user.last_login_at) : 'Never logged in'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="font-weight-bold text-muted">Two-Factor Auth:</td>
                            <td>
                                <span class="badge badge-${user.enable_two_factor ? 'success' : 'secondary'}">
                                    ${user.enable_two_factor ? 'Enabled' : 'Disabled'}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Email Notifications:</td>
                            <td>
                                <span class="badge badge-${user.email_notifications ? 'success' : 'secondary'}">
                                    ${user.email_notifications ? 'Enabled' : 'Disabled'}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Password Changed:</td>
                            <td>${user.password_changed_at ? formatDateTime(user.password_changed_at) : 'Never'}</td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold text-muted">Email Verified:</td>
                            <td>
                                <span class="badge badge-${user.email_verified_at ? 'success' : 'warning'}">
                                    ${user.email_verified_at ? 'Verified' : 'Pending'}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        `;
        
        $('#systemInfo').html(systemHtml);
    }

    function loadRoleInfo(role) {
        const rolePermissions = {
            'admin': {
                title: 'Administrator',
                description: 'Full system access with all permissions',
                permissions: [
                    'Manage all users and roles',
                    'Access all inventory modules',
                    'System configuration',
                    'View all reports and analytics',
                    'Backup and restore data'
                ]
            },
            'manager': {
                title: 'Manager',
                description: 'Department-level management access',
                permissions: [
                    'Manage department users',
                    'Approve allocations and maintenance',
                    'View department reports',
                    'Manage inventory items',
                    'Access dashboard analytics'
                ]
            },
            'employee': {
                title: 'Employee',
                description: 'Standard user access for daily operations',
                permissions: [
                    'View assigned inventory items',
                    'Request allocations',
                    'Report maintenance issues',
                    'Update item status',
                    'View basic reports'
                ]
            },
            'viewer': {
                title: 'Viewer',
                description: 'Read-only access to inventory data',
                permissions: [
                    'View inventory items',
                    'View allocation history',
                    'View maintenance records',
                    'Generate basic reports',
                    'No modification rights'
                ]
            }
        };

        if (role && rolePermissions[role]) {
            const info = rolePermissions[role];
            let html = `
                <div class="text-center mb-3">
                    <span class="badge badge-${getRoleBadgeClass(role)} badge-lg">${info.title}</span>
                </div>
                <p class="text-muted small text-center">${info.description}</p>
                <hr>
                <h6 class="font-weight-bold">Permissions:</h6>
                <ul class="list-unstyled">
            `;
            
            info.permissions.forEach(permission => {
                html += `<li class="mb-1"><i class="fas fa-check text-success mr-2"></i>${permission}</li>`;
            });
            
            html += '</ul>';
            $('#roleInfo').html(html);
        } else {
            $('#roleInfo').html('<p class="text-muted">Role information not available</p>');
        }
    }

    function loadQuickStats(userId) {
        $.get(`/api/inventory/users/${userId}/stats`)
            .done(function(stats) {
                const statsHtml = `
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h6 class="text-primary mb-0">${stats.items_managed || 0}</h6>
                            <small class="text-muted">Items Managed</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h6 class="text-info mb-0">${stats.allocations_made || 0}</h6>
                            <small class="text-muted">Allocations</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h6 class="text-success mb-0">${stats.maintenance_requests || 0}</h6>
                            <small class="text-muted">Maintenance</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h6 class="text-warning mb-0">${stats.reports_generated || 0}</h6>
                            <small class="text-muted">Reports</small>
                        </div>
                    </div>
                    <hr>
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>This Month Activity:</span>
                            <span class="font-weight-bold">${stats.monthly_activity || 0}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Average Session:</span>
                            <span class="font-weight-bold">${stats.avg_session_duration || '0m'}</span>
                        </div>
                    </div>
                `;
                
                $('#quickStats').html(statsHtml);
            })
            .fail(function() {
                $('#quickStats').html('<p class="text-muted small">Failed to load stats</p>');
            });
    }

    function loadActivityTimeline(userId) {
        $.get(`/api/inventory/users/${userId}/activity?limit=10`)
            .done(function(activities) {
                if (activities.length === 0) {
                    $('#activityTimeline').html('<p class="text-muted">No recent activity found</p>');
                    return;
                }
                
                let html = '<div class="timeline">';
                activities.forEach((activity, index) => {
                    html += `
                        <div class="timeline-item ${index === activities.length - 1 ? 'timeline-item-last' : ''}">
                            <div class="timeline-marker">
                                <i class="fas ${getActivityIcon(activity.type)} text-${getActivityColor(activity.type)}"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">${activity.description}</h6>
                                <p class="timeline-text text-muted">${activity.details || ''}</p>
                                <small class="timeline-time text-muted">
                                    <i class="fas fa-clock mr-1"></i>${formatDateTime(activity.created_at)}
                                </small>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                $('#activityTimeline').html(html);
            })
            .fail(function() {
                $('#activityTimeline').html('<p class="text-muted">Failed to load activity timeline</p>');
            });
    }

    function loadUserSessions(userId) {
        $.get(`/api/inventory/users/${userId}/sessions`)
            .done(function(sessions) {
                if (sessions.length === 0) {
                    $('#userSessions').html('<p class="text-muted">No active sessions found</p>');
                    return;
                }
                
                let html = '';
                sessions.forEach(session => {
                    const isCurrentSession = session.is_current;
                    html += `
                        <div class="d-flex align-items-center justify-content-between p-3 border rounded mb-2 ${isCurrentSession ? 'bg-light' : ''}">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas ${getDeviceIcon(session.device_type)} fa-2x text-muted"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">
                                        ${session.device_name || 'Unknown Device'}
                                        ${isCurrentSession ? '<span class="badge badge-success badge-sm ml-2">Current</span>' : ''}
                                    </h6>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-map-marker-alt mr-1"></i>${session.location || 'Unknown Location'}
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-clock mr-1"></i>Last active: ${formatDateTime(session.last_activity)}
                                    </p>
                                </div>
                            </div>
                            <div>
                                ${!isCurrentSession ? `
                                    <button class="btn btn-sm btn-outline-danger" onclick="terminateSession('${session.id}')">
                                        <i class="fas fa-times mr-1"></i>Terminate
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                
                $('#userSessions').html(html);
            })
            .fail(function() {
                $('#userSessions').html('<p class="text-muted">Failed to load user sessions</p>');
            });
    }

    function refreshActivity() {
        loadActivityTimeline(currentUserId);
        showToast('Activity timeline refreshed', 'success');
    }

    function editUser() {
        window.location.href = `/inventory/users/${currentUserId}/edit`;
    }

    function resetPassword() {
        $('#resetPasswordModal').modal('show');
    }

    function submitPasswordReset() {
        const formData = new FormData($('#resetPasswordForm')[0]);
        
        $.ajax({
            url: `/api/inventory/users/${currentUserId}/reset-password`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#resetPasswordModal').modal('hide');
                showToast('Password reset successfully!', 'success');
                
                // Reload user data to reflect changes
                loadUserData(currentUserId);
            },
            error: function(xhr) {
                let errorMessage = 'Failed to reset password';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showToast(errorMessage, 'error');
            }
        });
    }

    function toggleUserStatus() {
        const newStatus = currentUser.status === 'active' ? 'inactive' : 'active';
        const action = newStatus === 'active' ? 'activate' : 'deactivate';
        
        if (confirm(`Are you sure you want to ${action} this user?`)) {
            $.ajax({
                url: `/api/inventory/users/${currentUserId}/toggle-status`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showToast(`User ${action}d successfully!`, 'success');
                    loadUserData(currentUserId);
                },
                error: function(xhr) {
                    let errorMessage = `Failed to ${action} user`;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showToast(errorMessage, 'error');
                }
            });
        }
    }

    function sendWelcomeEmail() {
        if (confirm('Send welcome email to this user?')) {
            $.ajax({
                url: `/api/inventory/users/${currentUserId}/send-welcome`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showToast('Welcome email sent successfully!', 'success');
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to send welcome email';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showToast(errorMessage, 'error');
                }
            });
        }
    }

    function deleteUser() {
        $('#deleteUserModal').modal('show');
    }

    function confirmDeleteUser() {
        $.ajax({
            url: `/api/inventory/users/${currentUserId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $('#deleteUserModal').modal('hide');
                showToast('User deleted successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("inventory.users.index") }}';
                }, 1500);
            },
            error: function(xhr) {
                let errorMessage = 'Failed to delete user';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showToast(errorMessage, 'error');
            }
        });
    }

    function terminateSession(sessionId) {
        if (confirm('Are you sure you want to terminate this session?')) {
            $.ajax({
                url: `/api/inventory/users/${currentUserId}/sessions/${sessionId}/terminate`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showToast('Session terminated successfully!', 'success');
                    loadUserSessions(currentUserId);
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to terminate session';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showToast(errorMessage, 'error');
                }
            });
        }
    }

    function getStatusBadgeClass(status) {
        switch(status) {
            case 'active': return 'success';
            case 'inactive': return 'warning';
            case 'suspended': return 'danger';
            default: return 'secondary';
        }
    }

    function getRoleBadgeClass(role) {
        switch(role) {
            case 'admin': return 'danger';
            case 'manager': return 'primary';
            case 'employee': return 'info';
            case 'viewer': return 'secondary';
            default: return 'secondary';
        }
    }

    function getActivityIcon(type) {
        switch(type) {
            case 'login': return 'fa-sign-in-alt';
            case 'logout': return 'fa-sign-out-alt';
            case 'profile_update': return 'fa-user-edit';
            case 'password_change': return 'fa-key';
            case 'role_change': return 'fa-user-shield';
            case 'item_created': return 'fa-plus';
            case 'item_updated': return 'fa-edit';
            case 'allocation_made': return 'fa-share';
            case 'maintenance_request': return 'fa-wrench';
            default: return 'fa-circle';
        }
    }

    function getActivityColor(type) {
        switch(type) {
            case 'login': return 'success';
            case 'logout': return 'info';
            case 'profile_update': return 'primary';
            case 'password_change': return 'warning';
            case 'role_change': return 'danger';
            case 'item_created': return 'success';
            case 'item_updated': return 'info';
            case 'allocation_made': return 'primary';
            case 'maintenance_request': return 'warning';
            default: return 'secondary';
        }
    }

    function getDeviceIcon(deviceType) {
        switch(deviceType) {
            case 'desktop': return 'fa-desktop';
            case 'laptop': return 'fa-laptop';
            case 'tablet': return 'fa-tablet-alt';
            case 'mobile': return 'fa-mobile-alt';
            default: return 'fa-device';
        }
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

    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
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

<style>
    .badge-lg {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .timeline {
        position: relative;
        padding-left: 2rem;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 1rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e3e6f0;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
    }
    
    .timeline-item-last {
        margin-bottom: 0;
    }
    
    .timeline-marker {
        position: absolute;
        left: -2.5rem;
        top: 0;
        width: 2rem;
        height: 2rem;
        background: white;
        border: 2px solid #e3e6f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .timeline-content {
        background: #f8f9fc;
        padding: 1rem;
        border-radius: 0.35rem;
        border-left: 3px solid #4e73df;
    }
    
    .timeline-title {
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .timeline-text {
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
    }
    
    .timeline-time {
        font-size: 0.75rem;
    }
    
    .table-borderless td {
        border: none;
        padding: 0.5rem 0;
    }
    
    .table-borderless td:first-child {
        width: 40%;
    }
</style>
@endsection