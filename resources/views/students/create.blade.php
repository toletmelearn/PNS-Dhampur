@extends('layouts.app')

@section('title', 'Add New Student - PNS Dhampur')

@push('styles')
<style>
    .form-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .form-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .form-section {
        background: #f8fafc;
        border-left: 4px solid #667eea;
        padding: 1rem;
        margin: 1.5rem 0;
        border-radius: 0 10px 10px 0;
    }

    .form-section h5 {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 0;
    }

    .form-control, .form-select {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 25px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .file-upload-area {
        border: 2px dashed #cbd5e0;
        border-radius: 10px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .file-upload-area:hover {
        border-color: #667eea;
        background-color: #f7fafc;
    }

    .file-upload-area.dragover {
        border-color: #667eea;
        background-color: #edf2f7;
    }

    .required-field::after {
        content: " *";
        color: #e53e3e;
    }

    .error-message {
        color: #e53e3e;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .success-message {
        color: #38a169;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    .progress-bar-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card form-card">
                <div class="form-header">
                    <h2 class="mb-2">Student Registration</h2>
                    <p class="mb-0">Add a new student to the school management system</p>
                </div>
                
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading">Please correct the following errors:</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data" id="studentForm">
                        @csrf
                        
                        <!-- Personal Information Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">First Name</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                       name="first_name" value="{{ old('first_name') }}" required>
                                @error('first_name')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Last Name</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                       name="last_name" value="{{ old('last_name') }}" required>
                                @error('last_name')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                       name="date_of_birth" value="{{ old('date_of_birth') }}">
                                @error('date_of_birth')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select @error('gender') is-invalid @enderror" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Aadhaar Number</label>
                                <input type="text" class="form-control @error('aadhaar') is-invalid @enderror" 
                                       name="aadhaar" value="{{ old('aadhaar') }}" placeholder="XXXX-XXXX-XXXX">
                                @error('aadhaar')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Academic Information Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Admission Number</label>
                                <input type="text" class="form-control @error('admission_no') is-invalid @enderror" 
                                       name="admission_no" value="{{ old('admission_no') }}" placeholder="Auto-generated if empty">
                                @error('admission_no')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Class</label>
                                <select class="form-select @error('class') is-invalid @enderror" name="class">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ old('class') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}{{ $class->section ? ' - ' . $class->section : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('class')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Roll Number</label>
                                <input type="text" class="form-control @error('roll_number') is-invalid @enderror" 
                                       name="roll_number" value="{{ old('roll_number') }}">
                                @error('roll_number')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Family Information Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-users me-2"></i>Family Information</h5>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Father's Name</label>
                                <input type="text" class="form-control @error('father_name') is-invalid @enderror" 
                                       name="father_name" value="{{ old('father_name') }}">
                                @error('father_name')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mother's Name</label>
                                <input type="text" class="form-control @error('mother_name') is-invalid @enderror" 
                                       name="mother_name" value="{{ old('mother_name') }}">
                                @error('mother_name')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-phone me-2"></i>Contact Information</h5>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" class="form-control @error('contact_number') is-invalid @enderror" 
                                       name="contact_number" value="{{ old('contact_number') }}" placeholder="+91 XXXXX XXXXX">
                                @error('contact_number')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      name="address" rows="3">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Document Upload Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-file-upload me-2"></i>Document Upload</h5>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Birth Certificate</label>
                                <div class="file-upload-area" onclick="document.getElementById('birth_cert').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">Click to upload birth certificate</p>
                                    <small class="text-muted">PDF, JPG, PNG (Max: 2MB)</small>
                                </div>
                                <input type="file" id="birth_cert" name="birth_cert" class="d-none" 
                                       accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this, 'birth_cert_name')">
                                <div id="birth_cert_name" class="success-message mt-2" style="display: none;"></div>
                                @error('birth_cert')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aadhaar Card</label>
                                <div class="file-upload-area" onclick="document.getElementById('aadhaar_file').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">Click to upload Aadhaar card</p>
                                    <small class="text-muted">PDF, JPG, PNG (Max: 2MB)</small>
                                </div>
                                <input type="file" id="aadhaar_file" name="aadhaar_file" class="d-none" 
                                       accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this, 'aadhaar_file_name')">
                                <div id="aadhaar_file_name" class="success-message mt-2" style="display: none;"></div>
                                @error('aadhaar_file')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Other Documents</label>
                            <div class="file-upload-area" onclick="document.getElementById('other_docs').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                <p class="mb-0">Click to upload additional documents</p>
                                <small class="text-muted">Multiple files allowed - PDF, JPG, PNG (Max: 2MB each)</small>
                            </div>
                            <input type="file" id="other_docs" name="other_docs[]" class="d-none" 
                                   accept=".pdf,.jpg,.jpeg,.png" multiple onchange="updateFileNames(this, 'other_docs_names')">
                            <div id="other_docs_names" class="success-message mt-2" style="display: none;"></div>
                            @error('other_docs')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Students
                                    </a>
                                    <div>
                                        <button type="reset" class="btn btn-outline-warning me-2">
                                            <i class="fas fa-undo me-2"></i>Reset Form
                                        </button>
                                        <button type="submit" class="btn btn-primary-custom">
                                            <i class="fas fa-save me-2"></i>Save Student
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0">Saving student information...</p>
                <div class="progress mt-3">
                    <div class="progress-bar progress-bar-custom progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload handling with enhanced security
    const fileInputs = document.querySelectorAll('input[type="file"]');
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    const maxSize = 2 * 1024 * 1024; // 2MB

    fileInputs.forEach(input => {
        const dropZone = input.closest('.file-upload-area');
        
        // Drag and drop functionality
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelection(files[0], input);
            }
        });

        // File input change
        input.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                handleFileSelection(e.target.files[0], input);
            }
        });
    });

    function handleFileSelection(file, input) {
        // Validate file type
        if (!allowedTypes.includes(file.type)) {
            showAlert('Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.', 'error');
            input.value = '';
            return;
        }

        // Validate file size
        if (file.size > maxSize) {
            showAlert('File size exceeds 2MB limit. Please choose a smaller file.', 'error');
            input.value = '';
            return;
        }

        // Update file name display
        const displayId = input.id + '_name';
        updateFileName(input, displayId);
    }

    // Form validation and submission
    const form = document.getElementById('studentForm');
    
    // Form submission with enhanced validation and AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate required fields
        const requiredFields = ['first_name', 'last_name', 'date_of_birth', 'gender', 'class_id', 'father_name', 'contact_number'];
        let isValid = true;

        requiredFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            showAlert('Please fill in all required fields.', 'error');
            return;
        }

        // Validate email format if provided
        const email = document.querySelector('[name="email"]');
        if (email.value && !isValidEmail(email.value)) {
            email.classList.add('is-invalid');
            showAlert('Please enter a valid email address.', 'error');
            return;
        }

        // Validate contact number
        const contact = document.querySelector('[name="contact_number"]');
        if (!isValidPhone(contact.value)) {
            contact.classList.add('is-invalid');
            showAlert('Please enter a valid 10-digit contact number.', 'error');
            return;
        }

        // Show loading modal
        showLoadingModal();

        // Submit form via AJAX
        const formData = new FormData(this);
        
        fetch('/students', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoadingModal();
            
            if (data.success) {
                showAlert('Student registered successfully!', 'success');
                // Reset form
                this.reset();
                // Clear file displays
                document.querySelectorAll('[id$="_name"]').forEach(display => {
                    display.style.display = 'none';
                });
                
                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = '/students';
                }, 2000);
            } else {
                showAlert(data.message || 'An error occurred while registering the student.', 'error');
                
                // Show validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const fieldElement = document.querySelector(`[name="${field}"]`);
                        if (fieldElement) {
                            fieldElement.classList.add('is-invalid');
                        }
                    });
                }
            }
        })
        .catch(error => {
            hideLoadingModal();
            console.error('Error:', error);
            showAlert('An unexpected error occurred. Please try again.', 'error');
        });
    });

    // Real-time validation
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        field.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
});

// Utility functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[6-9]\d{9}$/;
    return phoneRegex.test(phone.replace(/\D/g, ''));
}

function updateFileName(input, displayId) {
    const display = document.getElementById(displayId);
    if (input.files.length > 0) {
        display.textContent = `Selected: ${input.files[0].name}`;
        display.style.display = 'block';
    } else {
        display.style.display = 'none';
    }
}

function showAlert(message, type) {
    // Create alert container if it doesn't exist
    let alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '9999';
        document.body.appendChild(alertContainer);
    }
    
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert ${alertClass} alert-dismissible fade show`;
    alertElement.innerHTML = `
        <i class="fas ${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    alertContainer.appendChild(alertElement);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertElement.parentNode) {
            alertElement.remove();
        }
    }, 5000);
}

function showLoadingModal() {
    const modal = document.getElementById('loadingModal');
    if (modal) {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

function hideLoadingModal() {
    const modal = document.getElementById('loadingModal');
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    }
}
</script>
@endpush