@extends('layouts.app')

@section('title', 'Bulk Import Users')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Bulk Import</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-import mr-2"></i>Bulk Import Users
            </h1>
            <p class="mb-0 text-muted">Import multiple users from CSV file</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.import-template') }}" class="btn btn-outline-primary">
                <i class="fas fa-download mr-1"></i>Download Template
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Back to Users
            </a>
        </div>
    </div>

    <!-- Import Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-upload mr-2"></i>Upload CSV File
                    </h6>
                </div>
                <div class="card-body">
                    <form id="bulkImportForm" action="{{ route('users.bulk-import.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- File Upload -->
                        <div class="form-group mb-4">
                            <label for="csv_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="csv_file" name="csv_file" accept=".csv" required>
                                <label class="custom-file-label" for="csv_file">Choose CSV file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Maximum file size: 10MB. Only CSV files are allowed.
                            </small>
                        </div>

                        <!-- Import Options -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="update_existing" name="update_existing" value="1">
                                        <label class="custom-control-label" for="update_existing">
                                            Update existing users
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        If checked, existing users with same email will be updated
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="send_welcome_email" name="send_welcome_email" value="1" checked>
                                        <label class="custom-control-label" for="send_welcome_email">
                                            Send welcome emails
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Send login credentials to new users via email
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Default Password -->
                        <div class="form-group mb-4">
                            <label for="default_password" class="form-label">Default Password</label>
                            <input type="text" class="form-control" id="default_password" name="default_password" placeholder="Leave empty to generate random passwords">
                            <small class="form-text text-muted">
                                If not provided, random passwords will be generated for each user
                            </small>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-upload mr-2"></i>Import Users
                            </button>
                            <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="resetForm()">
                                <i class="fas fa-undo mr-2"></i>Reset
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
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle mr-2"></i>Import Instructions
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold">CSV Format Requirements:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success mr-2"></i>First row must contain headers</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Required columns: name, email, role</li>
                        <li><i class="fas fa-check text-success mr-2"></i>Optional columns: password, phone, address</li>
                        <li><i class="fas fa-check text-success mr-2"></i>UTF-8 encoding recommended</li>
                    </ul>

                    <h6 class="font-weight-bold mt-4">Valid Roles:</h6>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge badge-primary">admin</span>
                        <span class="badge badge-info">principal</span>
                        <span class="badge badge-success">teacher</span>
                        <span class="badge badge-warning">accountant</span>
                        <span class="badge badge-secondary">librarian</span>
                        <span class="badge badge-light">student</span>
                        <span class="badge badge-dark">parent</span>
                    </div>

                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Important:</strong> Always backup your data before performing bulk imports.
                    </div>
                </div>
            </div>

            <!-- Sample Data -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-eye mr-2"></i>Sample Data
                    </h6>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded small">name,email,role,phone
John Doe,john@school.edu,teacher,+1234567890
Jane Smith,jane@school.edu,admin,+1234567891
Bob Wilson,bob@school.edu,student,+1234567892</pre>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Custom file input label update
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });

    // Form submission with loading state
    $('#bulkImportForm').on('submit', function(e) {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Importing...');
    });
});

function resetForm() {
    document.getElementById('bulkImportForm').reset();
    $('.custom-file-label').removeClass('selected').html('Choose CSV file...');
}

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