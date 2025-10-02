@extends('layouts.app')

@section('title', 'Add New Teacher')

@push('styles')
<style>
    .form-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .form-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .form-section {
        padding: 2rem;
        border-bottom: 1px solid #f1f3f4;
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .section-title {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
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
        border-radius: 10px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .file-upload-area {
        border: 2px dashed #e2e8f0;
        border-radius: 10px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .file-upload-area:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
    }

    .file-upload-area.dragover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.1);
    }

    .subject-checkbox {
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
    }

    .subject-checkbox:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
    }

    .subject-checkbox input:checked + label {
        color: #667eea;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="form-card">
                <!-- Header -->
                <div class="form-header">
                    <h2 class="mb-2">Add New Teacher</h2>
                    <p class="mb-0">Fill in the details to add a new teacher to the system</p>
                </div>

                <form action="{{ route('teachers.store') }}" method="POST" enctype="multipart/form-data" id="teacherForm">
                    @csrf

                    <!-- Personal Information -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-user me-2"></i>Personal Information
                        </h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       name="phone" value="{{ old('phone') }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="tel" class="form-control @error('emergency_contact') is-invalid @enderror" 
                                       name="emergency_contact" value="{{ old('emergency_contact') }}">
                                @error('emergency_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select class="form-select @error('blood_group') is-invalid @enderror" name="blood_group">
                                    <option value="">Select Blood Group</option>
                                    <option value="A+" {{ old('blood_group') == 'A+' ? 'selected' : '' }}>A+</option>
                                    <option value="A-" {{ old('blood_group') == 'A-' ? 'selected' : '' }}>A-</option>
                                    <option value="B+" {{ old('blood_group') == 'B+' ? 'selected' : '' }}>B+</option>
                                    <option value="B-" {{ old('blood_group') == 'B-' ? 'selected' : '' }}>B-</option>
                                    <option value="AB+" {{ old('blood_group') == 'AB+' ? 'selected' : '' }}>AB+</option>
                                    <option value="AB-" {{ old('blood_group') == 'AB-' ? 'selected' : '' }}>AB-</option>
                                    <option value="O+" {{ old('blood_group') == 'O+' ? 'selected' : '' }}>O+</option>
                                    <option value="O-" {{ old('blood_group') == 'O-' ? 'selected' : '' }}>O-</option>
                                </select>
                                @error('blood_group')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          name="address" rows="3">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-graduation-cap me-2"></i>Professional Information
                        </h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Qualification <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('qualification') is-invalid @enderror" 
                                       name="qualification" value="{{ old('qualification') }}" 
                                       placeholder="e.g., M.Sc Mathematics, B.Ed" required>
                                @error('qualification')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Experience (Years) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('experience_years') is-invalid @enderror" 
                                       name="experience_years" value="{{ old('experience_years') }}" 
                                       min="0" max="50" required>
                                @error('experience_years')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('salary') is-invalid @enderror" 
                                       name="salary" value="{{ old('salary') }}" 
                                       min="0" step="100" required>
                                @error('salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('joining_date') is-invalid @enderror" 
                                       name="joining_date" value="{{ old('joining_date') }}" required>
                                @error('joining_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Subjects -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-book me-2"></i>Teaching Subjects
                        </h4>
                        <div class="row">
                            @foreach($subjects as $subject)
                            <div class="col-md-4 mb-3">
                                <div class="subject-checkbox">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="subjects[]" value="{{ $subject->id }}" 
                                               id="subject_{{ $subject->id }}"
                                               {{ in_array($subject->id, old('subjects', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="subject_{{ $subject->id }}">
                                            {{ $subject->name }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @error('subjects')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Documents -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-file-upload me-2"></i>Documents
                        </h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Resume/CV</label>
                                <div class="file-upload-area" onclick="document.getElementById('resume').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">Click to upload resume</p>
                                    <small class="text-muted">PDF, DOC, DOCX (Max: 5MB)</small>
                                </div>
                                <input type="file" id="resume" name="documents[resume]" 
                                       class="d-none @error('documents.resume') is-invalid @enderror"
                                       accept=".pdf,.doc,.docx">
                                @error('documents.resume')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Certificates</label>
                                <div class="file-upload-area" onclick="document.getElementById('certificates').click()">
                                    <i class="fas fa-certificate fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">Click to upload certificates</p>
                                    <small class="text-muted">PDF, JPG, PNG (Max: 5MB)</small>
                                </div>
                                <input type="file" id="certificates" name="documents[certificates]" 
                                       class="d-none @error('documents.certificates') is-invalid @enderror"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('documents.certificates')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Photo</label>
                                <div class="file-upload-area" onclick="document.getElementById('photo').click()">
                                    <i class="fas fa-camera fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">Click to upload photo</p>
                                    <small class="text-muted">JPG, PNG (Max: 2MB)</small>
                                </div>
                                <input type="file" id="photo" name="documents[photo]" 
                                       class="d-none @error('documents.photo') is-invalid @enderror"
                                       accept=".jpg,.jpeg,.png">
                                @error('documents.photo')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Proof</label>
                                <div class="file-upload-area" onclick="document.getElementById('id_proof').click()">
                                    <i class="fas fa-id-card fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">Click to upload ID proof</p>
                                    <small class="text-muted">PDF, JPG, PNG (Max: 5MB)</small>
                                </div>
                                <input type="file" id="id_proof" name="documents[id_proof]" 
                                       class="d-none @error('documents.id_proof') is-invalid @enderror"
                                       accept=".pdf,.jpg,.jpeg,.png">
                                @error('documents.id_proof')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-section">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Teachers
                            </a>
                            <div class="d-flex gap-2">
                                <button type="reset" class="btn btn-outline-warning">
                                    <i class="fas fa-undo me-2"></i>Reset Form
                                </button>
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Add Teacher
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const uploadArea = this.parentElement.querySelector('.file-upload-area') || 
                              this.parentElement.previousElementSibling;
            
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
                
                uploadArea.innerHTML = `
                    <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                    <p class="mb-0 text-success">${fileName}</p>
                    <small class="text-muted">${fileSize} MB</small>
                `;
                uploadArea.style.borderColor = '#28a745';
                uploadArea.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
            }
        });
    });

    // Form validation
    const form = document.getElementById('teacherForm');
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });

    // Auto-format salary input
    const salaryInput = document.querySelector('input[name="salary"]');
    if (salaryInput) {
        salaryInput.addEventListener('input', function() {
            let value = this.value.replace(/,/g, '');
            if (value && !isNaN(value)) {
                this.value = parseInt(value).toLocaleString('en-IN');
            }
        });

        salaryInput.addEventListener('blur', function() {
            let value = this.value.replace(/,/g, '');
            if (value && !isNaN(value)) {
                this.value = value;
            }
        });
    }
});
</script>
@endpush