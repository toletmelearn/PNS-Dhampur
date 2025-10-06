@extends('layouts.app')

@section('title', 'Bulk Password Reset')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Bulk Password Reset</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-key mr-2"></i>Bulk Password Reset
            </h1>
            <p class="mb-0 text-muted">Reset passwords for multiple users at once</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Back to Users
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Reset Form -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users mr-2"></i>Select Users and Reset Options
                    </h6>
                </div>
                <div class="card-body">
                    <form id="bulkPasswordResetForm" action="{{ route('users.reset-passwords') }}" method="POST">
                        @csrf
                        
                        <!-- User Selection -->
                        <div class="form-group mb-4">
                            <label class="form-label">Select Users <span class="text-danger">*</span></label>
                            
                            <!-- Filter Options -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <select class="form-control" id="roleFilter" onchange="filterUsers()">
                                        <option value="">All Roles</option>
                                        <option value="admin">Admin</option>
                                        <option value="principal">Principal</option>
                                        <option value="teacher">Teacher</option>
                                        <option value="accountant">Accountant</option>
                                        <option value="librarian">Librarian</option>
                                        <option value="student">Student</option>
                                        <option value="parent">Parent</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="searchUsers" placeholder="Search by name or email..." onkeyup="filterUsers()">
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary" onclick="selectAll()">Select All</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="selectNone()">Select None</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Users List -->
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <div id="usersList">
                                    <!-- Users will be loaded here via JavaScript -->
                                    <div class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                        <p class="text-muted mt-2">Loading users...</p>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                <span id="selectedCount">0</span> users selected
                            </small>
                        </div>

                        <!-- Password Options -->
                        <div class="form-group mb-4">
                            <label class="form-label">Password Reset Type <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="password_type" id="random_password" value="random" checked onchange="togglePasswordOptions()">
                                <label class="form-check-label" for="random_password">
                                    Generate random passwords
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="password_type" id="custom_password" value="custom" onchange="togglePasswordOptions()">
                                <label class="form-check-label" for="custom_password">
                                    Set custom password for all selected users
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="password_type" id="default_password" value="default" onchange="togglePasswordOptions()">
                                <label class="form-check-label" for="default_password">
                                    Use default password (school123)
                                </label>
                            </div>
                        </div>

                        <!-- Custom Password Input -->
                        <div class="form-group mb-4" id="customPasswordGroup" style="display: none;">
                            <label for="custom_password_value" class="form-label">Custom Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="custom_password_value" name="custom_password_value" placeholder="Enter password for all users">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility()">
                                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Minimum 8 characters required
                            </small>
                        </div>

                        <!-- Email Options -->
                        <div class="form-group mb-4">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="send_email_notification" name="send_email_notification" value="1" checked>
                                <label class="custom-control-label" for="send_email_notification">
                                    Send email notifications to users
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Users will receive their new passwords via email
                            </small>
                        </div>

                        <!-- Force Password Change -->
                        <div class="form-group mb-4">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="force_password_change" name="force_password_change" value="1" checked>
                                <label class="custom-control-label" for="force_password_change">
                                    Force password change on next login
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Users will be required to change their password when they next log in
                            </small>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-danger btn-lg" id="submitBtn" disabled>
                                <i class="fas fa-key mr-2"></i>Reset Passwords
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="resetForm()">
                                <i class="fas fa-undo mr-2"></i>Reset Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Important Notice
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">Security Warning</h6>
                        <p class="mb-0">This action will reset passwords for all selected users. Make sure to:</p>
                        <ul class="mt-2 mb-0">
                            <li>Inform users about the password reset</li>
                            <li>Ensure email notifications are enabled</li>
                            <li>Consider forcing password change on next login</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle mr-2"></i>Password Options
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold">Random Passwords:</h6>
                    <p class="small text-muted">Generates secure 12-character passwords with mixed case, numbers, and symbols.</p>

                    <h6 class="font-weight-bold">Custom Password:</h6>
                    <p class="small text-muted">Sets the same password for all selected users. Useful for temporary access.</p>

                    <h6 class="font-weight-bold">Default Password:</h6>
                    <p class="small text-muted">Uses the system default password. Should be changed immediately by users.</p>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-shield-alt mr-2"></i>Security Features
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success mr-2"></i>Audit trail logging</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Email notifications</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Force password change</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Secure password generation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let allUsers = [];
let selectedUsers = [];

$(document).ready(function() {
    loadUsers();
});

function loadUsers() {
    $.get('/api/users')
        .done(function(response) {
            allUsers = response.data || response;
            renderUsers(allUsers);
        })
        .fail(function() {
            $('#usersList').html('<div class="alert alert-danger">Failed to load users</div>');
        });
}

function renderUsers(users) {
    let html = '';
    users.forEach(user => {
        const isSelected = selectedUsers.includes(user.id);
        html += `
            <div class="form-check user-item" data-role="${user.role}" data-name="${user.name.toLowerCase()}" data-email="${user.email.toLowerCase()}">
                <input class="form-check-input user-checkbox" type="checkbox" value="${user.id}" id="user_${user.id}" ${isSelected ? 'checked' : ''} onchange="updateSelection()">
                <label class="form-check-label w-100" for="user_${user.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${user.name}</strong>
                            <br><small class="text-muted">${user.email}</small>
                        </div>
                        <span class="badge badge-${getRoleBadgeClass(user.role)}">${user.role}</span>
                    </div>
                </label>
            </div>
        `;
    });
    $('#usersList').html(html);
    updateSelectedCount();
}

function getRoleBadgeClass(role) {
    const classes = {
        'admin': 'danger',
        'principal': 'warning',
        'teacher': 'success',
        'accountant': 'info',
        'librarian': 'secondary',
        'student': 'light',
        'parent': 'dark'
    };
    return classes[role] || 'secondary';
}

function filterUsers() {
    const roleFilter = $('#roleFilter').val();
    const searchTerm = $('#searchUsers').val().toLowerCase();
    
    $('.user-item').each(function() {
        const $item = $(this);
        const role = $item.data('role');
        const name = $item.data('name');
        const email = $item.data('email');
        
        const roleMatch = !roleFilter || role === roleFilter;
        const searchMatch = !searchTerm || name.includes(searchTerm) || email.includes(searchTerm);
        
        $item.toggle(roleMatch && searchMatch);
    });
}

function selectAll() {
    $('.user-item:visible .user-checkbox').prop('checked', true);
    updateSelection();
}

function selectNone() {
    $('.user-checkbox').prop('checked', false);
    updateSelection();
}

function updateSelection() {
    selectedUsers = [];
    $('.user-checkbox:checked').each(function() {
        selectedUsers.push(parseInt($(this).val()));
    });
    updateSelectedCount();
    $('#submitBtn').prop('disabled', selectedUsers.length === 0);
}

function updateSelectedCount() {
    $('#selectedCount').text(selectedUsers.length);
}

function togglePasswordOptions() {
    const customSelected = $('#custom_password').is(':checked');
    $('#customPasswordGroup').toggle(customSelected);
    
    if (customSelected) {
        $('#custom_password_value').attr('required', true);
    } else {
        $('#custom_password_value').attr('required', false);
    }
}

function togglePasswordVisibility() {
    const passwordField = $('#custom_password_value');
    const toggleIcon = $('#passwordToggleIcon');
    
    if (passwordField.attr('type') === 'password') {
        passwordField.attr('type', 'text');
        toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        passwordField.attr('type', 'password');
        toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

function resetForm() {
    document.getElementById('bulkPasswordResetForm').reset();
    selectedUsers = [];
    $('.user-checkbox').prop('checked', false);
    updateSelectedCount();
    $('#submitBtn').prop('disabled', true);
    $('#customPasswordGroup').hide();
    $('#roleFilter').val('');
    $('#searchUsers').val('');
    filterUsers();
}

// Form submission
$('#bulkPasswordResetForm').on('submit', function(e) {
    e.preventDefault();
    
    if (selectedUsers.length === 0) {
        toastr.error('Please select at least one user');
        return;
    }
    
    if (confirm(`Are you sure you want to reset passwords for ${selectedUsers.length} users?`)) {
        // Add selected users to form data
        selectedUsers.forEach(userId => {
            $(this).append(`<input type="hidden" name="user_ids[]" value="${userId}">`);
        });
        
        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Resetting...');
        
        this.submit();
    }
});

// Show success/error messages
@if(session('success'))
    toastr.success('{{ session('success') }}');
@endif

@if(session('error'))
    toastr.error('{{ session('error') }}');
@endif

@if($errors->any())
    @foreach($errors->all() as $error)
        toastr.error('{{ $error }}');
    @endforeach
@endif
</script>
@endpush
@endsection