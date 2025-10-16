@extends('layouts.app')

@section('title', 'Validation System Demo')

@section('header', 'Validation System Demo')

@section('header-actions')
    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <!-- Validation Demo Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-check-circle me-2"></i>Comprehensive Validation Demo
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Test Instructions:</strong> This form demonstrates both frontend and backend validation. 
                    Try submitting with invalid data to see real-time validation in action.
                </div>

                <!-- Student Registration Form -->
                <form id="studentValidationForm" 
                      action="{{ route('test.validation.submit') }}" 
                      method="POST" 
                      data-validate="true"
                      data-loading="true"
                      enctype="multipart/form-data">
                    @csrf

                    <!-- Personal Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-user me-2"></i>Personal Information
                            </h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="{{ old('first_name') }}"
                                   data-rules="required|min:2|max:50|alpha_spaces"
                                   placeholder="Enter first name">
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="{{ old('last_name') }}"
                                   data-rules="required|min:2|max:50|alpha_spaces"
                                   placeholder="Enter last name">
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}"
                                   data-rules="required|email|max:100"
                                   placeholder="Enter email address">
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone') }}"
                                   data-rules="required|indian_phone"
                                   placeholder="Enter 10-digit mobile number">
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_of_birth" 
                                   name="date_of_birth" 
                                   value="{{ old('date_of_birth') }}"
                                   data-rules="required|date|before:today"
                                   max="{{ date('Y-m-d', strtotime('-5 years')) }}">
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" 
                                    id="gender" 
                                    name="gender" 
                                    data-rules="required|in:male,female,other">
                                <option value="">Select Gender</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <div class="validation-error text-danger"></div>
                        </div>
                    </div>

                    <!-- Academic Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-graduation-cap me-2"></i>Academic Information
                            </h6>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="admission_number" class="form-label">Admission Number <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="admission_number" 
                                   name="admission_number" 
                                   value="{{ old('admission_number') }}"
                                   data-rules="required|admission_number"
                                   placeholder="e.g., ADM202300001">
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="roll_number" class="form-label">Roll Number <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="roll_number" 
                                   name="roll_number" 
                                   value="{{ old('roll_number') }}"
                                   data-rules="required|roll_number"
                                   placeholder="e.g., 2023001">
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="class_section" class="form-label">Class & Section <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="class_section" 
                                   name="class_section" 
                                   value="{{ old('class_section') }}"
                                   data-rules="required|class_section"
                                   placeholder="e.g., 10-A, 12-B">
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="academic_year" 
                                   name="academic_year" 
                                   value="{{ old('academic_year') }}"
                                   data-rules="required|academic_year"
                                   placeholder="e.g., 2023-2024">
                            <div class="validation-error text-danger"></div>
                        </div>
                    </div>

                    <!-- Address Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-map-marker-alt me-2"></i>Address Information
                            </h6>
                        </div>

                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">Full Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" 
                                      id="address" 
                                      name="address" 
                                      rows="3"
                                      data-rules="required|min:10|max:500"
                                      placeholder="Enter complete address">{{ old('address') }}</textarea>
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="city" 
                                   name="city" 
                                   value="{{ old('city') }}"
                                   data-rules="required|min:2|max:50|alpha_spaces"
                                   placeholder="Enter city name">
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="pincode" class="form-label">PIN Code <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="pincode" 
                                   name="pincode" 
                                   value="{{ old('pincode') }}"
                                   data-rules="required|digits:6"
                                   placeholder="Enter 6-digit PIN code">
                            <div class="validation-error text-danger"></div>
                        </div>
                    </div>

                    <!-- File Upload Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-file-upload me-2"></i>Document Upload
                            </h6>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="profile_photo" class="form-label">Profile Photo</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="profile_photo" 
                                   name="profile_photo" 
                                   accept="image/*"
                                   data-rules="image|max:2048">
                            <div class="form-text">Maximum file size: 2MB. Allowed formats: JPG, PNG, GIF</div>
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="documents" class="form-label">Additional Documents</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="documents" 
                                   name="documents[]" 
                                   multiple
                                   accept=".pdf,.doc,.docx"
                                   data-rules="mimes:pdf,doc,docx|max:5120">
                            <div class="form-text">Maximum file size: 5MB per file. Allowed formats: PDF, DOC, DOCX</div>
                            <div class="validation-error text-danger"></div>
                        </div>
                    </div>

                    <!-- Security Test Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-danger border-bottom pb-2 mb-3">
                                <i class="fas fa-shield-alt me-2"></i>Security Test Section (Try malicious inputs)
                            </h6>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="test_xss" class="form-label">XSS Test Field</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="test_xss" 
                                   name="test_xss" 
                                   value="{{ old('test_xss') }}"
                                   placeholder="Try: <script>alert('XSS')</script>">
                            <div class="form-text text-muted">Try entering XSS payloads to test security</div>
                            <div class="validation-error text-danger"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="test_sql" class="form-label">SQL Injection Test Field</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="test_sql" 
                                   name="test_sql" 
                                   value="{{ old('test_sql') }}"
                                   placeholder="Try: ' OR '1'='1">
                            <div class="form-text text-muted">Try entering SQL injection payloads to test security</div>
                            <div class="validation-error text-danger"></div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo me-2"></i>Reset Form
                                </button>
                                
                                <div>
                                    <button type="button" class="btn btn-warning me-2" onclick="testValidation()">
                                        <i class="fas fa-bug me-2"></i>Test Validation
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check me-2"></i>Submit Form
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Test Results Card -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>Validation Test Results
                </h5>
            </div>
            <div class="card-body">
                <div id="testResults" class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Submit the form or click "Test Validation" to see results here.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JavaScript for Demo -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test validation function
    window.testValidation = function() {
        const form = document.getElementById('studentValidationForm');
        const resultsDiv = document.getElementById('testResults');
        
        // Clear previous results
        resultsDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Running validation tests...';
        resultsDiv.className = 'alert alert-info';
        
        // Test cases
        const testCases = [
            {
                name: 'Empty Form Validation',
                action: () => {
                    // Clear all fields
                    form.querySelectorAll('input, select, textarea').forEach(field => {
                        field.value = '';
                    });
                    // Trigger validation
                    if (window.validationSystem) {
                        return window.validationSystem.validateForm(form);
                    }
                    return false;
                }
            },
            {
                name: 'Invalid Email Format',
                action: () => {
                    document.getElementById('email').value = 'invalid-email';
                    if (window.validationSystem) {
                        return window.validationSystem.validateField(document.getElementById('email'));
                    }
                    return false;
                }
            },
            {
                name: 'Invalid Phone Number',
                action: () => {
                    document.getElementById('phone').value = '123456789';
                    if (window.validationSystem) {
                        return window.validationSystem.validateField(document.getElementById('phone'));
                    }
                    return false;
                }
            },
            {
                name: 'XSS Attack Prevention',
                action: () => {
                    document.getElementById('test_xss').value = '<script>alert("XSS")</script>';
                    if (window.validationSystem) {
                        return window.validationSystem.validateField(document.getElementById('test_xss'));
                    }
                    return false;
                }
            }
        ];
        
        // Run tests
        setTimeout(() => {
            let results = '<h6 class="mb-3">Validation Test Results:</h6>';
            let allPassed = true;
            
            testCases.forEach((testCase, index) => {
                try {
                    const result = testCase.action();
                    const status = !result ? 'PASS' : 'FAIL';
                    const statusClass = !result ? 'text-success' : 'text-danger';
                    const icon = !result ? 'fa-check-circle' : 'fa-times-circle';
                    
                    results += `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>${testCase.name}</span>
                            <span class="${statusClass}">
                                <i class="fas ${icon} me-1"></i>${status}
                            </span>
                        </div>
                    `;
                    
                    if (result) allPassed = false;
                } catch (error) {
                    results += `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>${testCase.name}</span>
                            <span class="text-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>ERROR
                            </span>
                        </div>
                    `;
                    allPassed = false;
                }
            });
            
            results += `
                <hr>
                <div class="text-center">
                    <strong class="${allPassed ? 'text-success' : 'text-warning'}">
                        Overall Status: ${allPassed ? 'All Tests Passed' : 'Some Tests Failed (Expected for Demo)'}
                    </strong>
                </div>
            `;
            
            resultsDiv.innerHTML = results;
            resultsDiv.className = `alert ${allPassed ? 'alert-success' : 'alert-warning'}`;
        }, 1000);
    };
    
    // Reset form function
    window.resetForm = function() {
        const form = document.getElementById('studentValidationForm');
        form.reset();
        
        // Clear validation errors
        form.querySelectorAll('.validation-error').forEach(error => {
            error.textContent = '';
        });
        
        // Remove validation classes
        form.querySelectorAll('.is-invalid, .is-valid').forEach(field => {
            field.classList.remove('is-invalid', 'is-valid');
        });
        
        // Clear test results
        document.getElementById('testResults').innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            Submit the form or click "Test Validation" to see results here.
        `;
        document.getElementById('testResults').className = 'alert alert-info';
    };
});
</script>
@endsection

@push('styles')
<style>
    .validation-error {
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
    
    .is-valid {
        border-color: #198754 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
    }
    
    .card {
        border: none;
        border-radius: 10px;
    }
    
    .card-header {
        border-radius: 10px 10px 0 0 !important;
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    .btn {
        border-radius: 6px;
    }
    
    .alert {
        border-radius: 8px;
    }
</style>
@endpush