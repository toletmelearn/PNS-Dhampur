@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Edit User</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-edit mr-2"></i>Edit User
            </h1>
            <p class="mb-0 text-muted">Update user information and system access</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.users.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Back to Users
            </a>
            <button type="button" class="btn btn-info" onclick="viewUserDetails()">
                <i class="fas fa-eye mr-1"></i>View Details
            </button>
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
            <!-- Main Form -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-user-edit mr-2"></i>User Information
                        </h6>
                        <div class="d-flex gap-2">
                            <span id="userStatus" class="badge badge-secondary">Loading...</span>
                            <small class="text-muted">Last updated: <span id="lastUpdated">-</span></small>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="editUserForm" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="userId" name="user_id">
                            
                            <!-- Personal Information -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fas fa-user mr-2"></i>Personal Information
                                </h5>
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="firstName">First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="firstName" name="first_name" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="lastName">Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="lastName" name="last_name" required>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email Address <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   required
                                                   pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                                   title="Please enter a valid email address">
                                            <div class="invalid-feedback"></div>
                                            <small class="form-text text-muted">Used for login and notifications</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   placeholder="+91 XXXXX XXXXX"
                                                   pattern="[+]?[0-9]{10,15}"
                                                   title="Please enter a valid phone number (10-15 digits)">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dateOfBirth">Date of Birth</label>
                                            <input type="date" class="form-control" id="dateOfBirth" name="date_of_birth">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="gender">Gender</label>
                                            <select class="form-control" id="gender" name="gender">
                                                <option value="">Select Gender</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                                <option value="other">Other</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter full address"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <!-- Professional Information -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fas fa-briefcase mr-2"></i>Professional Information
                                </h5>
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="employeeId">Employee ID</label>
                                            <input type="text" class="form-control" id="employeeId" name="employee_id" readonly>
                                            <div class="invalid-feedback"></div>
                                            <small class="form-text text-muted">Employee ID cannot be changed</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="designation">Designation</label>
                                            <input type="text" class="form-control" id="designation" name="designation" placeholder="e.g., Manager, Executive">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="department">Department <span class="text-danger">*</span></label>
                                            <select class="form-control" id="department" name="department_id" required>
                                                <option value="">Select Department</option>
                                                <!-- Departments will be loaded dynamically -->
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="manager">Reporting Manager</label>
                                            <select class="form-control" id="manager" name="manager_id">
                                                <option value="">Select Manager</option>
                                                <!-- Managers will be loaded dynamically -->
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="joiningDate">Joining Date</label>
                                            <input type="date" class="form-control" id="joiningDate" name="joining_date">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="salary">Salary</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">₹</span>
                                                </div>
                                                <input type="number" class="form-control" id="salary" name="salary" placeholder="0.00" step="0.01">
                                            </div>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- System Access -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fas fa-key mr-2"></i>System Access
                                </h5>
                                <hr>
                                
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
                                            <div class="invalid-feedback"></div>
                                            <small class="form-text text-muted">Determines system permissions</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status">Account Status <span class="text-danger">*</span></label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                                <option value="suspended">Suspended</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Password Change Section -->
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="changePassword" name="change_password">
                                        <label class="custom-control-label" for="changePassword">
                                            Change Password
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="passwordSection" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">New Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="password" name="password">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="invalid-feedback"></div>
                                                <small class="form-text text-muted">Minimum 8 characters with letters and numbers</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="confirmPassword">Confirm New Password</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="confirmPassword" name="password_confirmation">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Picture -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fas fa-image mr-2"></i>Profile Picture
                                </h5>
                                <hr>
                                
                                <!-- Current Profile Picture -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label>Current Profile Picture</label>
                                        <div id="currentAvatar" class="mb-3">
                                            <!-- Current avatar will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="avatar">Upload New Profile Picture</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="avatar" name="avatar" accept="image/*" onchange="previewImage(this)">
                                        <label class="custom-file-label" for="avatar">Choose file...</label>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                    <small class="form-text text-muted">Max size: 2MB. Formats: JPG, PNG, GIF. Leave blank to keep current picture.</small>
                                    
                                    <!-- New Image Preview -->
                                    <div id="imagePreview" class="mt-3" style="display: none;">
                                        <label>New Profile Picture Preview</label>
                                        <div>
                                            <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                            <button type="button" class="btn btn-sm btn-danger ml-2" onclick="removeImage()">
                                                <i class="fas fa-times"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Settings -->
                            <div class="form-section mb-4">
                                <h5 class="section-title">
                                    <i class="fas fa-cog mr-2"></i>Additional Settings
                                </h5>
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="forcePasswordChange" name="force_password_change">
                                                <label class="custom-control-label" for="forcePasswordChange">
                                                    Force password change on next login
                                                </label>
                                            </div>
                                            
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="enableTwoFactor" name="enable_two_factor">
                                                <label class="custom-control-label" for="enableTwoFactor">
                                                    Enable two-factor authentication
                                                </label>
                                            </div>
                                            
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="emailNotifications" name="email_notifications">
                                                <label class="custom-control-label" for="emailNotifications">
                                                    Enable email notifications
                                                </label>
                                            </div>
                                            
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="sendUpdateEmail" name="send_update_email">
                                                <label class="custom-control-label" for="sendUpdateEmail">
                                                    Send email notification about profile update
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="button" class="btn btn-warning" onclick="resetForm()">
                                            <i class="fas fa-undo mr-1"></i>Reset Changes
                                        </button>
                                    </div>
                                    <div>
                                        <a href="{{ route('inventory.users.index') }}" class="btn btn-outline-secondary mr-2">
                                            <i class="fas fa-times mr-1"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="fas fa-save mr-1"></i>Update User
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- User Summary -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-user mr-2"></i>User Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="userSummary">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Loading user summary...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle mr-2"></i>Role Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="roleInfo">
                            <p class="text-muted">Select a role to see permissions</p>
                        </div>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-history mr-2"></i>Recent Activity
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="activityLog">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm text-info" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted small">Loading activity...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Requirements (shown when changing password) -->
                <div class="card shadow mb-4" id="passwordRequirementsCard" style="display: none;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-warning">
                            <i class="fas fa-shield-alt mr-2"></i>Password Requirements
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0" id="passwordRequirements">
                            <li class="mb-1">
                                <i class="fas fa-times text-danger mr-2" id="req-length"></i>
                                At least 8 characters
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-times text-danger mr-2" id="req-letter"></i>
                                At least one letter
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-times text-danger mr-2" id="req-number"></i>
                                At least one number
                            </li>
                            <li class="mb-1">
                                <i class="fas fa-times text-danger mr-2" id="req-special"></i>
                                At least one special character
                            </li>
                            <li>
                                <i class="fas fa-times text-danger mr-2" id="req-match"></i>
                                Passwords must match
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
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
                <!-- User details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let originalUserData = {};
    let currentUserId = null;

    $(document).ready(function() {
        // Get user ID from URL
        const urlParts = window.location.pathname.split('/');
        currentUserId = urlParts[urlParts.length - 2]; // Assuming URL is /users/{id}/edit
        
        if (currentUserId) {
            loadUserData(currentUserId);
        } else {
            showToast('Invalid user ID', 'error');
            window.location.href = '{{ route("inventory.users.index") }}';
        }
        
        loadDepartments();
        loadManagers();
        
        // Event handlers
        $('#role').on('change', function() {
            updateRoleInfo($(this).val());
        });
        
        $('#changePassword').on('change', function() {
            togglePasswordSection($(this).is(':checked'));
        });
        
        $('#password, #confirmPassword').on('keyup', function() {
            if ($('#changePassword').is(':checked')) {
                validatePassword();
            }
        });
        
        $('#editUserForm').on('submit', function(e) {
            e.preventDefault();
            if (validateForm()) {
                submitForm();
            }
        });
    });

    function loadUserData(userId) {
        $.get(`/api/inventory/users/${userId}`)
            .done(function(user) {
                originalUserData = user;
                populateForm(user);
                loadUserSummary(user);
                loadUserActivity(userId);
                
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

    function populateForm(user) {
        $('#userId').val(user.id);
        $('#firstName').val(user.first_name);
        $('#lastName').val(user.last_name);
        $('#email').val(user.email);
        $('#phone').val(user.phone);
        $('#dateOfBirth').val(user.date_of_birth);
        $('#gender').val(user.gender);
        $('#address').val(user.address);
        $('#employeeId').val(user.employee_id);
        $('#designation').val(user.designation);
        $('#department').val(user.department_id);
        $('#manager').val(user.manager_id);
        $('#joiningDate').val(user.joining_date);
        $('#salary').val(user.salary);
        $('#role').val(user.role);
        $('#status').val(user.status);
        
        // Checkboxes
        $('#forcePasswordChange').prop('checked', user.force_password_change);
        $('#enableTwoFactor').prop('checked', user.enable_two_factor);
        $('#emailNotifications').prop('checked', user.email_notifications);
        
        // Update status badge
        updateStatusBadge(user.status);
        
        // Update last updated
        $('#lastUpdated').text(formatDateTime(user.updated_at));
        
        // Load current avatar
        loadCurrentAvatar(user.avatar);
        
        // Update role info
        updateRoleInfo(user.role);
    }

    function loadCurrentAvatar(avatarUrl) {
        if (avatarUrl) {
            $('#currentAvatar').html(`
                <img src="${avatarUrl}" alt="Current Avatar" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
            `);
        } else {
            $('#currentAvatar').html(`
                <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                    <i class="fas fa-user fa-3x text-muted"></i>
                </div>
                <small class="text-muted d-block mt-2">No profile picture</small>
            `);
        }
    }

    function loadUserSummary(user) {
        const createdDate = new Date(user.created_at);
        const daysSinceCreated = Math.floor((new Date() - createdDate) / (1000 * 60 * 60 * 24));
        
        $('#userSummary').html(`
            <div class="text-center mb-3">
                <h5 class="mb-1">${user.first_name} ${user.last_name}</h5>
                <p class="text-muted mb-2">${user.email}</p>
                <span class="badge badge-${getStatusBadgeClass(user.status)}">${user.status.toUpperCase()}</span>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <h6 class="text-primary">${daysSinceCreated}</h6>
                    <small class="text-muted">Days Active</small>
                </div>
                <div class="col-6">
                    <h6 class="text-info">${user.login_count || 0}</h6>
                    <small class="text-muted">Total Logins</small>
                </div>
            </div>
            <hr>
            <div class="small">
                <div class="d-flex justify-content-between mb-1">
                    <span>Employee ID:</span>
                    <span class="font-weight-bold">${user.employee_id || 'N/A'}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Department:</span>
                    <span class="font-weight-bold">${user.department?.name || 'N/A'}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Role:</span>
                    <span class="badge badge-${getRoleBadgeClass(user.role)}">${user.role}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Last Login:</span>
                    <span class="font-weight-bold">${user.last_login_at ? formatDateTime(user.last_login_at) : 'Never'}</span>
                </div>
            </div>
        `);
    }

    function loadUserActivity(userId) {
        $.get(`/api/inventory/users/${userId}/activity`)
            .done(function(activities) {
                if (activities.length === 0) {
                    $('#activityLog').html('<p class="text-muted small">No recent activity</p>');
                    return;
                }
                
                let html = '';
                activities.slice(0, 5).forEach(activity => {
                    html += `
                        <div class="d-flex align-items-center mb-2">
                            <div class="mr-2">
                                <i class="fas ${getActivityIcon(activity.type)} text-${getActivityColor(activity.type)}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small font-weight-bold">${activity.description}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">${formatDateTime(activity.created_at)}</div>
                            </div>
                        </div>
                    `;
                });
                
                $('#activityLog').html(html);
            })
            .fail(function() {
                $('#activityLog').html('<p class="text-muted small">Failed to load activity</p>');
            });
    }

    function loadDepartments() {
        $.get('/api/inventory/departments')
            .done(function(data) {
                let options = '<option value="">Select Department</option>';
                data.forEach(dept => {
                    options += `<option value="${dept.id}">${dept.name}</option>`;
                });
                $('#department').html(options);
                
                // Set the current department if user data is loaded
                if (originalUserData.department_id) {
                    $('#department').val(originalUserData.department_id);
                }
            })
            .fail(function() {
                showToast('Failed to load departments', 'error');
            });
    }

    function loadManagers() {
        $.get('/api/inventory/users?role=manager,admin')
            .done(function(data) {
                let options = '<option value="">Select Manager</option>';
                data.data.forEach(user => {
                    // Don't include the current user as their own manager
                    if (user.id !== currentUserId) {
                        options += `<option value="${user.id}">${user.first_name} ${user.last_name} (${user.role})</option>`;
                    }
                });
                $('#manager').html(options);
                
                // Set the current manager if user data is loaded
                if (originalUserData.manager_id) {
                    $('#manager').val(originalUserData.manager_id);
                }
            })
            .fail(function() {
                console.error('Failed to load managers');
            });
    }

    function updateRoleInfo(role) {
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
                <h6 class="text-primary">${info.title}</h6>
                <p class="text-muted small">${info.description}</p>
                <h6 class="mt-3">Permissions:</h6>
                <ul class="list-unstyled">
            `;
            
            info.permissions.forEach(permission => {
                html += `<li class="mb-1"><i class="fas fa-check text-success mr-2"></i>${permission}</li>`;
            });
            
            html += '</ul>';
            $('#roleInfo').html(html);
        } else {
            $('#roleInfo').html('<p class="text-muted">Select a role to see permissions</p>');
        }
    }

    function togglePasswordSection(show) {
        if (show) {
            $('#passwordSection').show();
            $('#passwordRequirementsCard').show();
            $('#password').attr('required', true);
            $('#confirmPassword').attr('required', true);
        } else {
            $('#passwordSection').hide();
            $('#passwordRequirementsCard').hide();
            $('#password').removeAttr('required').val('');
            $('#confirmPassword').removeAttr('required').val('');
            
            // Reset password requirements
            $('#passwordRequirements i').removeClass('fa-check text-success').addClass('fa-times text-danger');
        }
    }

    function validatePassword() {
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        
        // Length check
        updateRequirement('req-length', password.length >= 8);
        
        // Letter check
        updateRequirement('req-letter', /[a-zA-Z]/.test(password));
        
        // Number check
        updateRequirement('req-number', /\d/.test(password));
        
        // Special character check
        updateRequirement('req-special', /[!@#$%^&*(),.?":{}|<>]/.test(password));
        
        // Match check
        updateRequirement('req-match', password && confirmPassword && password === confirmPassword);
    }

    function updateRequirement(id, met) {
        const element = $(`#${id}`);
        if (met) {
            element.removeClass('fa-times text-danger').addClass('fa-check text-success');
        } else {
            element.removeClass('fa-check text-success').addClass('fa-times text-danger');
        }
    }

    function validateForm() {
        let isValid = true;
        
        // Clear previous validation
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Required field validation
        const requiredFields = ['firstName', 'lastName', 'email', 'department', 'role', 'status'];
        
        requiredFields.forEach(field => {
            const value = $(`#${field}`).val().trim();
            if (!value) {
                $(`#${field}`).addClass('is-invalid');
                $(`#${field}`).siblings('.invalid-feedback').text('This field is required');
                isValid = false;
            }
        });
        
        // Email validation
        const email = $('#email').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            $('#email').addClass('is-invalid');
            $('#email').siblings('.invalid-feedback').text('Please enter a valid email address');
            isValid = false;
        }
        
        // Password validation (if changing password)
        if ($('#changePassword').is(':checked')) {
            const password = $('#password').val();
            const confirmPassword = $('#confirmPassword').val();
            
            if (password.length < 8) {
                $('#password').addClass('is-invalid');
                $('#password').siblings('.invalid-feedback').text('Password must be at least 8 characters');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                $('#confirmPassword').addClass('is-invalid');
                $('#confirmPassword').siblings('.invalid-feedback').text('Passwords do not match');
                isValid = false;
            }
        }
        
        return isValid;
    }

    function submitForm() {
        const formData = new FormData($('#editUserForm')[0]);
        
        // Remove password fields if not changing password
        if (!$('#changePassword').is(':checked')) {
            formData.delete('password');
            formData.delete('password_confirmation');
        }
        
        // Disable submit button
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Updating...');
        
        $.ajax({
            url: `/api/inventory/users/${currentUserId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showToast('User updated successfully!', 'success');
                
                // Update original data
                originalUserData = response.user;
                
                // Update UI elements
                updateStatusBadge(response.user.status);
                $('#lastUpdated').text(formatDateTime(response.user.updated_at));
                
                // Reload user summary and activity
                loadUserSummary(response.user);
                loadUserActivity(currentUserId);
                
                // Reset password section if it was shown
                if ($('#changePassword').is(':checked')) {
                    $('#changePassword').prop('checked', false);
                    togglePasswordSection(false);
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to update user';
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    // Handle validation errors
                    if (xhr.responseJSON.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(field => {
                            const fieldElement = $(`[name="${field}"]`);
                            fieldElement.addClass('is-invalid');
                            fieldElement.siblings('.invalid-feedback').text(xhr.responseJSON.errors[field][0]);
                        });
                    }
                }
                
                showToast(errorMessage, 'error');
            },
            complete: function() {
                // Re-enable submit button
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Update User');
            }
        });
    }

    function updateStatusBadge(status) {
        const badgeClass = getStatusBadgeClass(status);
        $('#userStatus').removeClass('badge-secondary badge-success badge-warning badge-danger')
                        .addClass(`badge-${badgeClass}`)
                        .text(status.toUpperCase());
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
            default: return 'secondary';
        }
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#imagePreview').show();
            };
            
            reader.readAsDataURL(input.files[0]);
            
            // Update file label
            const fileName = input.files[0].name;
            $(input).siblings('.custom-file-label').text(fileName);
        }
    }

    function removeImage() {
        $('#avatar').val('');
        $('#imagePreview').hide();
        $('.custom-file-label').text('Choose file...');
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

    function resetForm() {
        if (confirm('Are you sure you want to reset all changes? This will restore the original user data.')) {
            populateForm(originalUserData);
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            $('#imagePreview').hide();
            $('.custom-file-label').text('Choose file...');
            
            // Reset password section
            $('#changePassword').prop('checked', false);
            togglePasswordSection(false);
            
            showToast('Form reset to original values', 'info');
        }
    }

    function viewUserDetails() {
        // Load comprehensive user details in modal
        $.get(`/api/inventory/users/${currentUserId}/details`)
            .done(function(user) {
                const detailsHtml = `
                    <div class="row">
                        <div class="col-md-4 text-center">
                            ${user.avatar ? 
                                `<img src="${user.avatar}" alt="Avatar" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">` :
                                `<div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px;">
                                    <i class="fas fa-user fa-4x text-muted"></i>
                                </div>`
                            }
                            <h5>${user.first_name} ${user.last_name}</h5>
                            <p class="text-muted">${user.email}</p>
                            <span class="badge badge-${getStatusBadgeClass(user.status)} badge-lg">${user.status.toUpperCase()}</span>
                        </div>
                        <div class="col-md-8">
                            <h6>Personal Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Phone:</strong></td><td>${user.phone || 'N/A'}</td></tr>
                                <tr><td><strong>Date of Birth:</strong></td><td>${user.date_of_birth || 'N/A'}</td></tr>
                                <tr><td><strong>Gender:</strong></td><td>${user.gender || 'N/A'}</td></tr>
                                <tr><td><strong>Address:</strong></td><td>${user.address || 'N/A'}</td></tr>
                            </table>
                            
                            <h6 class="mt-3">Professional Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Employee ID:</strong></td><td>${user.employee_id || 'N/A'}</td></tr>
                                <tr><td><strong>Designation:</strong></td><td>${user.designation || 'N/A'}</td></tr>
                                <tr><td><strong>Department:</strong></td><td>${user.department?.name || 'N/A'}</td></tr>
                                <tr><td><strong>Manager:</strong></td><td>${user.manager ? `${user.manager.first_name} ${user.manager.last_name}` : 'N/A'}</td></tr>
                                <tr><td><strong>Joining Date:</strong></td><td>${user.joining_date || 'N/A'}</td></tr>
                                <tr><td><strong>Salary:</strong></td><td>${user.salary ? `₹${parseFloat(user.salary).toLocaleString()}` : 'N/A'}</td></tr>
                            </table>
                            
                            <h6 class="mt-3">System Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Role:</strong></td><td><span class="badge badge-${getRoleBadgeClass(user.role)}">${user.role}</span></td></tr>
                                <tr><td><strong>Created:</strong></td><td>${formatDateTime(user.created_at)}</td></tr>
                                <tr><td><strong>Last Updated:</strong></td><td>${formatDateTime(user.updated_at)}</td></tr>
                                <tr><td><strong>Last Login:</strong></td><td>${user.last_login_at ? formatDateTime(user.last_login_at) : 'Never'}</td></tr>
                                <tr><td><strong>Two-Factor Auth:</strong></td><td>${user.enable_two_factor ? 'Enabled' : 'Disabled'}</td></tr>
                                <tr><td><strong>Email Notifications:</strong></td><td>${user.email_notifications ? 'Enabled' : 'Disabled'}</td></tr>
                            </table>
                        </div>
                    </div>
                `;
                
                $('#userDetailsContent').html(detailsHtml);
                $('#userDetailsModal').modal('show');
            })
            .fail(function() {
                showToast('Failed to load user details', 'error');
            });
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
    .form-section {
        border-left: 3px solid #e3e6f0;
        padding-left: 1rem;
    }
    
    .section-title {
        color: #5a5c69;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }
    
    .form-actions {
        background-color: #f8f9fc;
        margin: -1.25rem -1.25rem -1.25rem -1.25rem;
        padding: 1.25rem;
        border-radius: 0 0 0.35rem 0.35rem;
    }
    
    .custom-file-label::after {
        content: "Browse";
    }
    
    .img-thumbnail {
        border: 2px solid #e3e6f0;
    }
    
    .badge-lg {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    #userSummary .row {
        margin: 0;
    }
    
    #userSummary .col-6 {
        padding: 0.5rem;
    }
</style>
@endsection