@extends('layouts.app')

@section('title', 'Edit Teacher - ' . $teacher->user->name)

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

    .file-upload-area.has-file {
        border-color: #28a745;
        background: rgba(40, 167, 69, 0.1);
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

    .current-file {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
    }

    .current-file a {
        color: #667eea;
        text-decoration: none;
    }

    .current-file a:hover {
        text-decoration: underline;
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
                    <h2 class="mb-2">Edit Teacher</h2>
                    <p class="mb-0">Update {{ $teacher->user->name }}'s information</p>
                </div>

                <form action="{{ route('teachers.update', $teacher->id) }}" method="POST" enctype="multipart/form-data" id="teacherForm">
                    @csrf
                    @method('PUT')

                    <!-- Personal Information -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-user me-2"></i>Personal Information
                        </h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       name="name" value="{{ old('name', $teacher->user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       name="email" value="{{ old('email', $teacher->user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       name="phone" value="{{ old('phone', $teacher->user->phone) }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Emergency Contact</label>
                                <input type="tel" class="form-control @error('emergency_contact') is-invalid @enderror" 
                                       name="emergency_contact" value="{{ old('emergency_contact', $teacher->user->emergency_contact) }}">
                                @error('emergency_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Blood Group</label>
                                <select class="form-select @error('blood_group') is-invalid @enderror" name="blood_group">
                                    <option value="">Select Blood Group</option>
                                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bloodGroup)
                                        <option value="{{ $bloodGroup }}" 
                                                {{ old('blood_group', $teacher->user->blood_group) == $bloodGroup ? 'selected' : '' }}>
                                            {{ $bloodGroup }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('blood_group')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          name="address" rows="3">{{ old('address', $teacher->user->address) }}</textarea>
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
                                       name="qualification" value="{{ old('qualification', $teacher->qualification) }}" 
                                       placeholder="e.g., M.Sc Mathematics, B.Ed" required>
                                @error('qualification')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Experience (Years) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('experience_years') is-invalid @enderror" 
                                       name="experience_years" value="{{ old('experience_years', $teacher->experience_years) }}" 
                                       min="0" max="50" required>
                                @error('experience_years')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Salary (₹) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('salary') is-invalid @enderror" 
                                       name="salary" value="{{ old('salary', $teacher->salary) }}" 
                                       min="0" step="100" required>
                                @error('salary')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('joining_date') is-invalid @enderror" 
                                       name="joining_date" value="{{ old('joining_date', $teacher->joining_date?->format('Y-m-d')) }}" required>
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
                                               {{ in_array($subject->id, old('subjects', $teacher->subjects->pluck('id')->toArray())) ? 'checked' : '' }}>
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
                            @foreach(['resume', 'certificates', 'photo', 'id_proof'] as $docType)
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ ucfirst(str_replace('_', ' ', $docType)) }}</label>
                                
                                @if(isset($teacher->documents[$docType]))
                                <div class="current-file">
                                    <i class="fas fa-file me-2"></i>
                                    <a href="{{ Storage::url($teacher->documents[$docType]) }}" target="_blank">
                                        Current {{ $docType }}
                                    </a>
                                    <small class="text-muted d-block">Click to view current file</small>
                                </div>
                                @endif

                                <div class="file-upload-area {{ isset($teacher->documents[$docType]) ? 'has-file' : '' }}" 
                                     onclick="document.getElementById('{{ $docType }}').click()">
                                    @if(isset($teacher->documents[$docType]))
                                        <i class="fas fa-sync-alt fa-2x mb-2 text-primary"></i>
                                        <p class="mb-0">Click to replace {{ $docType }}</p>
                                    @else
                                        <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted"></i>
                                        <p class="mb-0">Click to upload {{ $docType }}</p>
                                    @endif
                                    <small class="text-muted">
                                        @if($docType == 'photo')
                                            JPG, PNG (Max: 2MB)
                                        @else
                                            PDF, DOC, DOCX, JPG, PNG (Max: 5MB)
                                        @endif
                                    </small>
                                </div>
                                <input type="file" id="{{ $docType }}" name="documents[{{ $docType }}]" 
                                       class="d-none @error('documents.'.$docType) is-invalid @enderror"
                                       accept="{{ $docType == 'photo' ? '.jpg,.jpeg,.png' : '.pdf,.doc,.docx,.jpg,.jpeg,.png' }}">
                                @error('documents.'.$docType)
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-section">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('teachers.show', $teacher->id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Teacher
                            </a>
                            <div class="d-flex gap-2">
                                <a href="{{ route('teachers.index') }}" class="btn btn-outline-info">
                                    <i class="fas fa-list me-2"></i>All Teachers
                                </a>
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="fas fa-save me-2"></i>Update Teacher
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
                    <small class="text-muted">${fileSize} MB - Ready to upload</small>
                `;
                uploadArea.classList.add('has-file');
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