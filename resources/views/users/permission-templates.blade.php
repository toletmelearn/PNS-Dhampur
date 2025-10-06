@extends('layouts.app')

@section('title', 'Permission Templates')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Permission Templates</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user-shield mr-2"></i>Permission Templates
            </h1>
            <p class="mb-0 text-muted">Predefined permission sets for different user roles</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Back to Users
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Apply Template Form -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users mr-2"></i>Apply Template to Users
                    </h6>
                </div>
                <div class="card-body">
                    <form id="applyTemplateForm" action="{{ route('users.apply-permission-template') }}" method="POST">
                        @csrf
                        
                        <!-- Template Selection -->
                        <div class="form-group mb-3">
                            <label for="template_role" class="form-label">Select Template <span class="text-danger">*</span></label>
                            <select class="form-control" id="template_role" name="template_role" required onchange="showTemplatePreview()">
                                <option value="">Choose a role template...</option>
                                <option value="admin">Admin Template</option>
                                <option value="principal">Principal Template</option>
                                <option value="teacher">Teacher Template</option>
                                <option value="accountant">Accountant Template</option>
                                <option value="librarian">Librarian Template</option>
                                <option value="student">Student Template</option>
                                <option value="parent">Parent Template</option>
                            </select>
                        </div>

                        <!-- User Selection -->
                        <div class="form-group mb-3">
                            <label class="form-label">Select Users <span class="text-danger">*</span></label>
                            
                            <!-- Filter Options -->
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" id="searchUsers" placeholder="Search users..." onkeyup="filterUsers()">
                            </div>

                            <!-- Users List -->
                            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                <div id="usersList">
                                    <div class="text-center py-3">
                                        <i class="fas fa-spinner fa-spin text-muted"></i>
                                        <p class="small text-muted mt-1">Loading users...</p>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                <span id="selectedCount">0</span> users selected
                            </small>
                        </div>

                        <!-- Options -->
                        <div class="form-group mb-3">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="overwrite_existing" name="overwrite_existing" value="1">
                                <label class="custom-control-label" for="overwrite_existing">
                                    Overwrite existing permissions
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Replace all current permissions with template permissions
                            </small>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block" id="applyBtn" disabled>
                                <i class="fas fa-check mr-2"></i>Apply Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Templates Display -->
        <div class="col-lg-8">
            <div class="row" id="templatesContainer">
                <!-- Templates will be loaded here -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let allUsers = [];
let selectedUsers = [];
let templates = {};

$(document).ready(function() {
    loadUsers();
    loadTemplates();
});

function loadUsers() {
    $.get('/api/users')
        .done(function(response) {
            allUsers = response.data || response;
            renderUsers(allUsers);
        })
        .fail(function() {
            $('#usersList').html('<div class="alert alert-danger small">Failed to load users</div>');
        });
}

function loadTemplates() {
    $.get('{{ route('users.permission-templates') }}')
        .done(function(response) {
            templates = response;
            renderTemplates();
        })
        .fail(function() {
            $('#templatesContainer').html('<div class="col-12"><div class="alert alert-danger">Failed to load templates</div></div>');
        });
}

function renderUsers(users) {
    let html = '';
    users.forEach(user => {
        const isSelected = selectedUsers.includes(user.id);
        html += `
            <div class="form-check form-check-sm user-item" data-name="${user.name.toLowerCase()}" data-email="${user.email.toLowerCase()}">
                <input class="form-check-input user-checkbox" type="checkbox" value="${user.id}" id="user_${user.id}" ${isSelected ? 'checked' : ''} onchange="updateSelection()">
                <label class="form-check-label small w-100" for="user_${user.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${user.name}</strong>
                            <br><small class="text-muted">${user.email}</small>
                        </div>
                        <span class="badge badge-${getRoleBadgeClass(user.role)} badge-sm">${user.role}</span>
                    </div>
                </label>
            </div>
        `;
    });
    $('#usersList').html(html);
    updateSelectedCount();
}

function renderTemplates() {
    let html = '';
    
    Object.keys(templates).forEach(role => {
        const template = templates[role];
        const permissions = template.permissions || [];
        
        html += `
            <div class="col-lg-6 mb-4">
                <div class="card shadow template-card" data-role="${role}">
                    <div class="card-header py-2 bg-${getTemplateColor(role)}">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-${getTemplateIcon(role)} mr-2"></i>${template.name}
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <p class="small text-muted mb-2">${template.description}</p>
                        
                        <h6 class="font-weight-bold mb-2">Permissions (${permissions.length}):</h6>
                        <div class="permissions-list" style="max-height: 150px; overflow-y: auto;">
                            ${permissions.map(permission => `
                                <span class="badge badge-light badge-sm mr-1 mb-1">${permission}</span>
                            `).join('')}
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectTemplate('${role}')">
                                <i class="fas fa-check mr-1"></i>Select Template
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="previewTemplate('${role}')">
                                <i class="fas fa-eye mr-1"></i>Preview
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#templatesContainer').html(html);
}

function getTemplateColor(role) {
    const colors = {
        'admin': 'danger',
        'principal': 'warning',
        'teacher': 'success',
        'accountant': 'info',
        'librarian': 'secondary',
        'student': 'light',
        'parent': 'dark'
    };
    return colors[role] || 'primary';
}

function getTemplateIcon(role) {
    const icons = {
        'admin': 'user-cog',
        'principal': 'user-tie',
        'teacher': 'chalkboard-teacher',
        'accountant': 'calculator',
        'librarian': 'book',
        'student': 'user-graduate',
        'parent': 'users'
    };
    return icons[role] || 'user';
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

function selectTemplate(role) {
    $('#template_role').val(role);
    showTemplatePreview();
    
    // Highlight selected template
    $('.template-card').removeClass('border-primary');
    $(`.template-card[data-role="${role}"]`).addClass('border-primary');
}

function showTemplatePreview() {
    const selectedRole = $('#template_role').val();
    if (selectedRole && templates[selectedRole]) {
        // You can add a preview modal or section here if needed
        console.log('Selected template:', templates[selectedRole]);
    }
}

function previewTemplate(role) {
    const template = templates[role];
    if (!template) return;
    
    // Create modal content
    const modalContent = `
        <div class="modal fade" id="templatePreviewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-${getTemplateColor(role)}">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-${getTemplateIcon(role)} mr-2"></i>${template.name} Template
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">${template.description}</p>
                        
                        <h6 class="font-weight-bold">Permissions:</h6>
                        <div class="row">
                            ${template.permissions.map(permission => `
                                <div class="col-md-6 mb-2">
                                    <i class="fas fa-check text-success mr-2"></i>${permission}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="selectTemplate('${role}')" data-dismiss="modal">
                            Select This Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal and add new one
    $('#templatePreviewModal').remove();
    $('body').append(modalContent);
    $('#templatePreviewModal').modal('show');
}

function filterUsers() {
    const searchTerm = $('#searchUsers').val().toLowerCase();
    
    $('.user-item').each(function() {
        const $item = $(this);
        const name = $item.data('name');
        const email = $item.data('email');
        
        const searchMatch = !searchTerm || name.includes(searchTerm) || email.includes(searchTerm);
        $item.toggle(searchMatch);
    });
}

function updateSelection() {
    selectedUsers = [];
    $('.user-checkbox:checked').each(function() {
        selectedUsers.push(parseInt($(this).val()));
    });
    updateSelectedCount();
    
    const templateSelected = $('#template_role').val() !== '';
    $('#applyBtn').prop('disabled', selectedUsers.length === 0 || !templateSelected);
}

function updateSelectedCount() {
    $('#selectedCount').text(selectedUsers.length);
}

// Form submission
$('#applyTemplateForm').on('submit', function(e) {
    e.preventDefault();
    
    if (selectedUsers.length === 0) {
        toastr.error('Please select at least one user');
        return;
    }
    
    const templateRole = $('#template_role').val();
    if (!templateRole) {
        toastr.error('Please select a permission template');
        return;
    }
    
    if (confirm(`Apply ${templateRole} template to ${selectedUsers.length} users?`)) {
        // Add selected users to form data
        selectedUsers.forEach(userId => {
            $(this).append(`<input type="hidden" name="user_ids[]" value="${userId}">`);
        });
        
        const applyBtn = $('#applyBtn');
        applyBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Applying...');
        
        this.submit();
    }
});

// Template role change handler
$('#template_role').on('change', function() {
    updateSelection();
    showTemplatePreview();
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