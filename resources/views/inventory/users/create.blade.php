@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('inventory.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Create User</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-plus mr-2"></i>Create New User
            </h1>
            <p class="mb-0 text-muted">Add a new user to the inventory management system</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.users.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Back to Users
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Form -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-edit mr-2"></i>User Information
                    </h6>
                </div>
                <div class="card-body">
                    <form id="createUserForm" enctype="multipart/form-data">
                        @csrf
                        
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
                                        <small class="form-text text-muted">This will be used for login</small>
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
                                        <input type="text" class="form-control" id="employeeId" name="employee_id" placeholder="EMP001">
                                        <div class="invalid-feedback"></div>
                                        <small class="form-text text-muted">Leave blank to auto-generate</small>
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
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" required>
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
                                        <label for="confirmPassword">Confirm Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" required>
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

                        <!-- Profile Picture -->
                        <div class="form-section mb-4">
                            <h5 class="section-title">
                                <i class="fas fa-image mr-2"></i>Profile Picture
                            </h5>
                            <hr>
                            
                            <div class="form-group">
                                <label for="avatar">Upload Profile Picture</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="avatar" name="avatar" accept="image/*" onchange="previewImage(this)">
                                    <label class="custom-file-label" for="avatar">Choose file...</label>
                                </div>
                                <div class="invalid-feedback"></div>
                                <small class="form-text text-muted">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                                
                                <!-- Image Preview -->
                                <div id="imagePreview" class="mt-3" style="display: none;">
                                    <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                    <button type="button" class="btn btn-sm btn-danger ml-2" onclick="removeImage()">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
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
                                            <input type="checkbox" class="custom-control-input" id="sendWelcomeEmail" name="send_welcome_email" checked>
                                            <label class="custom-control-label" for="sendWelcomeEmail">
                                                Send welcome email with login credentials
                                            </label>
                                        </div>
                                        
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input" id="forcePasswordChange" name="force_password_change">
                                            <label class="custom-control-label" for="forcePasswordChange">
                                                Force password change on first login
                                            </label>
                                        </div>
                                        
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input" id="enableTwoFactor" name="enable_two_factor">
                                            <label class="custom-control-label" for="enableTwoFactor">
                                                Enable two-factor authentication
                                            </label>
                                        </div>
                                        
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="emailNotifications" name="email_notifications" checked>
                                            <label class="custom-control-label" for="emailNotifications">
                                                Enable email notifications
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
                                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                        <i class="fas fa-undo mr-1"></i>Reset Form
                                    </button>
                                </div>
                                <div>
                                    <a href="{{ route('inventory.users.index') }}" class="btn btn-outline-secondary mr-2">
                                        <i class="fas fa-times mr-1"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success" id="submitBtn">
                                        <i class="fas fa-user-plus mr-1"></i>Create User
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

            <!-- Quick Tips -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-lightbulb mr-2"></i>Quick Tips
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Use strong passwords with at least 8 characters
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Employee ID will be auto-generated if left blank
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Profile pictures should be under 2MB
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success mr-2"></i>
                            Welcome emails help users get started quickly
                        </li>
                        <li>
                            <i class="fas fa-check text-success mr-2"></i>
                            Two-factor authentication enhances security
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Password Requirements -->
            <div class="card shadow mb-4">
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
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        loadDepartments();
        loadManagers();
        
        // Role change handler
        $('#role').on('change', function() {
            updateRoleInfo($(this).val());
        });
        
        // Password validation
        $('#password, #confirmPassword').on('keyup', function() {
            validatePassword();
        });
        
        // Form validation
        $('#createUserForm').on('submit', function(e) {
            e.preventDefault();
            if (validateForm()) {
                submitForm();
            }
        });
    });

    function loadDepartments() {
        $.get('/api/inventory/departments')
            .done(function(data) {
                let options = '<option value="">Select Department</option>';
                data.forEach(dept => {
                    options += `<option value="${dept.id}">${dept.name}</option>`;
                });
                $('#department').html(options);
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
                    options += `<option value="${user.id}">${user.first_name} ${user.last_name} (${user.role})</option>`;
                });
                $('#manager').html(options);
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

    function validatePassword() {
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        
        // Length check
        if (password.length >= 8) {
            updateRequirement('req-length', true);
        } else {
            updateRequirement('req-length', false);
        }
        
        // Letter check
        if (/[a-zA-Z]/.test(password)) {
            updateRequirement('req-letter', true);
        } else {
            updateRequirement('req-letter', false);
        }
        
        // Number check
        if (/\d/.test(password)) {
            updateRequirement('req-number', true);
        } else {
            updateRequirement('req-number', false);
        }
        
        // Special character check
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            updateRequirement('req-special', true);
        } else {
            updateRequirement('req-special', false);
        }
        
        // Match check
        if (password && confirmPassword && password === confirmPassword) {
            updateRequirement('req-match', true);
        } else {
            updateRequirement('req-match', false);
        }
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
        const requiredFields = ['firstName', 'lastName', 'email', 'department', 'role', 'status', 'password', 'confirmPassword'];
        
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
        
        // Password validation
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
        
        return isValid;
    }

    function submitForm() {
        const formData = new FormData($('#createUserForm')[0]);
        
        // Disable submit button
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Creating...');
        
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
                showToast('User created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("inventory.users.index") }}';
                }, 1500);
            },
            error: function(xhr) {
                let errorMessage = 'Failed to create user';
                
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
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-user-plus mr-1"></i>Create User');
            }
        });
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
        if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
            $('#createUserForm')[0].reset();
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            $('#imagePreview').hide();
            $('.custom-file-label').text('Choose file...');
            $('#roleInfo').html('<p class="text-muted">Select a role to see permissions</p>');
            
            // Reset password requirements
            $('#passwordRequirements i').removeClass('fa-check text-success').addClass('fa-times text-danger');
        }
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
    
    .password-strength {
        height: 5px;
        border-radius: 3px;
        margin-top: 5px;
    }
    
    .strength-weak { background-color: #dc3545; }
    .strength-medium { background-color: #ffc107; }
    .strength-strong { background-color: #28a745; }
</style>
@endsection