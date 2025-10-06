@extends('layouts.app')

@section('title', 'Add New Student - PNS Dhampur')

@push('styles')
<link href="{{ asset('css/file-upload-enhanced.css') }}" rel="stylesheet">
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
                                       name="aadhaar" id="aadhaar" value="{{ old('aadhaar') }}" 
                                       placeholder="XXXX-XXXX-XXXX" 
                                       minlength="12" maxlength="14" 
                                       pattern="[0-9]{4}[-\s]?[0-9]{4}[-\s]?[0-9]{4}"
                                       data-validation="aadhaar"
                                       autocomplete="off">
                                <div class="invalid-feedback" id="aadhaar-error"></div>
                                <div class="valid-feedback" id="aadhaar-success">
                                    <i class="fas fa-check-circle me-1"></i>Valid Aadhaar number
                                </div>
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
                                <div class="drop-zone" id="birth-cert-drop-zone">
                                    <div class="drop-zone-content">
                                        <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                        <div class="drop-zone-text">Drag & Drop birth certificate here</div>
                                        <div class="drop-zone-subtext">or click to browse files</div>
                                        <input type="file" id="birth_cert" name="birth_cert" 
                                               accept=".pdf,.jpg,.jpeg,.png" 
                                               data-max-size="{{ config('fileupload.max_file_sizes.document') }}"
                                               style="display: none;">
                                    </div>
                                </div>
                                <div class="file-preview mt-3" id="birth-cert-preview"></div>
                                <small class="form-text text-muted">
                                    Supported formats: PDF, JPG, PNG. Maximum size: {{ number_format(config('fileupload.max_file_sizes.document') / 1024, 0) }}MB
                                </small>
                                @error('birth_cert')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Aadhaar Card</label>
                                <div class="drop-zone" id="aadhaar-drop-zone">
                                    <div class="drop-zone-content">
                                        <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                        <div class="drop-zone-text">Drag & Drop Aadhaar card here</div>
                                        <div class="drop-zone-subtext">or click to browse files</div>
                                        <input type="file" id="aadhaar_file" name="aadhaar_file" 
                                               accept=".pdf,.jpg,.jpeg,.png" 
                                               data-max-size="{{ config('fileupload.max_file_sizes.document') }}"
                                               style="display: none;">
                                    </div>
                                </div>
                                <div class="file-preview mt-3" id="aadhaar-preview"></div>
                                <small class="form-text text-muted">
                                    Supported formats: PDF, JPG, PNG. Maximum size: {{ number_format(config('fileupload.max_file_sizes.document') / 1024, 0) }}MB
                                </small>
                                @error('aadhaar_file')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Other Documents</label>
                            <div class="drop-zone" id="other-docs-drop-zone">
                                <div class="drop-zone-content">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                    <div class="drop-zone-text">Drag & Drop additional documents here</div>
                                    <div class="drop-zone-subtext">or click to browse files (multiple files allowed)</div>
                                    <input type="file" id="other_docs" name="other_docs[]" 
                                           accept=".pdf,.jpg,.jpeg,.png" 
                                           data-max-size="{{ config('fileupload.max_file_sizes.document') }}"
                                           multiple style="display: none;">
                                </div>
                            </div>
                            <div class="file-preview mt-3" id="other-docs-preview"></div>
                            <small class="form-text text-muted">
                                Multiple files allowed - PDF, JPG, PNG. Maximum size: {{ number_format(config('fileupload.max_file_sizes.document') / 1024, 0) }}MB each
                            </small>
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
<script src="{{ asset('js/file-upload-enhanced.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize enhanced file upload for birth certificate
    const birthCertUploader = new EnhancedFileUpload({
        maxFileSize: {{ config('fileupload.max_file_sizes.document') }},
        allowedTypes: {!! json_encode(explode(',', config('fileupload.allowed_file_types.document.extensions'))) !!},
        dropZone: '#birth-cert-drop-zone',
        fileInput: '#birth_cert',
        previewContainer: '#birth-cert-preview',
        autoUpload: false
    });

    // Initialize enhanced file upload for Aadhaar
    const aadhaarUploader = new EnhancedFileUpload({
        maxFileSize: {{ config('fileupload.max_file_sizes.document') }},
        allowedTypes: {!! json_encode(explode(',', config('fileupload.allowed_file_types.document.extensions'))) !!},
        dropZone: '#aadhaar-drop-zone',
        fileInput: '#aadhaar_file',
        previewContainer: '#aadhaar-preview',
        autoUpload: false
    });

    // Initialize enhanced file upload for other documents (multiple files)
    const otherDocsUploader = new EnhancedFileUpload({
        maxFileSize: {{ config('fileupload.max_file_sizes.document') }},
        allowedTypes: {!! json_encode(explode(',', config('fileupload.allowed_file_types.document.extensions'))) !!},
        dropZone: '#other-docs-drop-zone',
        fileInput: '#other_docs',
        previewContainer: '#other-docs-preview',
        autoUpload: false,
        multiple: true
    });

    // Original form validation and submission logic

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

        // Validate Aadhaar number if provided
        const aadhaar = document.querySelector('[name="aadhaar"]');
        if (aadhaar.value.trim()) {
            const aadhaarValidation = isValidAadhaar(aadhaar.value);
            if (!aadhaarValidation.valid) {
                aadhaar.classList.add('is-invalid');
                showAlert(`Aadhaar validation failed: ${aadhaarValidation.message}`, 'error');
                return;
            }
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

    // Enhanced Aadhaar validation with real-time feedback
    const aadhaarInput = document.getElementById('aadhaar');
    const aadhaarError = document.getElementById('aadhaar-error');
    const aadhaarSuccess = document.getElementById('aadhaar-success');

    if (aadhaarInput) {
        // Format input as user types
        aadhaarInput.addEventListener('input', function(e) {
            const cursorPosition = e.target.selectionStart;
            const oldValue = e.target.value;
            const newValue = formatAadhaar(e.target.value);
            
            e.target.value = newValue;
            
            // Adjust cursor position after formatting
            const diff = newValue.length - oldValue.length;
            e.target.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
            
            // Real-time validation
            validateAadhaarField();
        });

        // Validate on blur
        aadhaarInput.addEventListener('blur', function() {
            validateAadhaarField();
        });

        // Prevent non-numeric input (except hyphens and spaces)
        aadhaarInput.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            if (!/[0-9\-\s]/.test(char) && e.which !== 8 && e.which !== 0) {
                e.preventDefault();
            }
        });

        function validateAadhaarField() {
            const value = aadhaarInput.value.trim();
            
            // Clear previous states
            aadhaarInput.classList.remove('is-valid', 'is-invalid');
            aadhaarError.textContent = '';
            aadhaarSuccess.style.display = 'none';
            
            if (value === '') {
                return; // Empty is allowed (not required field)
            }
            
            const validation = isValidAadhaar(value);
            
            if (validation.valid) {
                aadhaarInput.classList.add('is-valid');
                aadhaarSuccess.style.display = 'block';
            } else {
                aadhaarInput.classList.add('is-invalid');
                aadhaarError.textContent = validation.message;
            }
        }
    }
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

function isValidAadhaar(aadhaar) {
    // Remove spaces and hyphens
    const cleanAadhaar = aadhaar.replace(/[\s-]/g, '');
    
    // Check if it's exactly 12 digits
    if (!/^\d{12}$/.test(cleanAadhaar)) {
        return { valid: false, message: 'Aadhaar must be exactly 12 digits' };
    }
    
    // Check for invalid patterns (all same digits, sequential numbers)
    if (/^(\d)\1{11}$/.test(cleanAadhaar)) {
        return { valid: false, message: 'Aadhaar cannot have all same digits' };
    }
    
    if (/^(0123456789|1234567890|9876543210|0987654321)/.test(cleanAadhaar)) {
        return { valid: false, message: 'Invalid Aadhaar pattern detected' };
    }
    
    // Verhoeff algorithm validation for Aadhaar
    if (!verhoeffCheck(cleanAadhaar)) {
        return { valid: false, message: 'Invalid Aadhaar number (checksum failed)' };
    }
    
    return { valid: true, message: 'Valid Aadhaar number' };
}

function verhoeffCheck(aadhaar) {
    // Verhoeff algorithm implementation for Aadhaar validation
    const d = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
        [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
        [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
        [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
        [9, 8, 7, 6, 5, 4, 3, 2, 1, 0]
    ];
    
    const p = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
        [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
        [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
        [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
        [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
        [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
        [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
    ];
    
    let c = 0;
    const myArray = aadhaar.split('').reverse();
    
    for (let i = 0; i < myArray.length; i++) {
        c = d[c][p[((i + 1) % 8)][parseInt(myArray[i])]];
    }
    
    return c === 0;
}

function formatAadhaar(value) {
    // Remove all non-digits
    const digits = value.replace(/\D/g, '');
    
    // Format as XXXX-XXXX-XXXX
    if (digits.length <= 4) {
        return digits;
    } else if (digits.length <= 8) {
        return digits.slice(0, 4) + '-' + digits.slice(4);
    } else {
        return digits.slice(0, 4) + '-' + digits.slice(4, 8) + '-' + digits.slice(8, 12);
    }
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