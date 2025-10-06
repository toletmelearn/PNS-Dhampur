@extends('layouts.app')

@section('title', 'Add Academic Year')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Add Academic Year</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.index') }}">Configuration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.academic-years.index') }}">Academic Years</a></li>
                        <li class="breadcrumb-item active">Add Academic Year</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Add New Academic Year</h5>
                    
                    <form action="{{ route('admin.configuration.academic-years.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Academic Year Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required
                                           placeholder="e.g., 2024-2025">
                                    <div class="form-text">Format: YYYY-YYYY (e.g., 2024-2025)</div>
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control @error('description') is-invalid @enderror" 
                                           id="description" name="description" value="{{ old('description') }}"
                                           placeholder="Brief description (optional)">
                                    @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                           id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                                    @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                                    @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                    <div class="form-text">Active academic years are available for selection</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_current" name="is_current" value="1" 
                                           {{ old('is_current') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_current">
                                        Set as Current Academic Year
                                    </label>
                                    <div class="form-text">This will deactivate the current academic year</div>
                                </div>
                            </div>
                        </div>

                        <!-- Academic Year Settings -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Academic Year Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="total_working_days" class="form-label">Total Working Days</label>
                                            <input type="number" class="form-control @error('settings.total_working_days') is-invalid @enderror" 
                                                   id="total_working_days" name="settings[total_working_days]" 
                                                   value="{{ old('settings.total_working_days', 200) }}" min="1">
                                            @error('settings.total_working_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="minimum_attendance" class="form-label">Minimum Attendance (%)</label>
                                            <input type="number" class="form-control @error('settings.minimum_attendance') is-invalid @enderror" 
                                                   id="minimum_attendance" name="settings[minimum_attendance]" 
                                                   value="{{ old('settings.minimum_attendance', 75) }}" min="0" max="100">
                                            @error('settings.minimum_attendance')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="grace_period_days" class="form-label">Grace Period (Days)</label>
                                            <input type="number" class="form-control @error('settings.grace_period_days') is-invalid @enderror" 
                                                   id="grace_period_days" name="settings[grace_period_days]" 
                                                   value="{{ old('settings.grace_period_days', 7) }}" min="0">
                                            <div class="form-text">Days after start date for late admissions</div>
                                            @error('settings.grace_period_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="allow_late_admissions" 
                                                   name="settings[allow_late_admissions]" value="1" 
                                                   {{ old('settings.allow_late_admissions', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allow_late_admissions">
                                                Allow Late Admissions
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="auto_promote_students" 
                                                   name="settings[auto_promote_students]" value="1" 
                                                   {{ old('settings.auto_promote_students') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="auto_promote_students">
                                                Auto Promote Students
                                            </label>
                                            <div class="form-text">Automatically promote students at year end</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.configuration.academic-years.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i> Save Academic Year
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const nameInput = document.getElementById('name');

    // Auto-generate name based on dates
    function updateName() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && !nameInput.value) {
            const startYear = startDate.getFullYear();
            const endYear = endDate.getFullYear();
            nameInput.value = `${startYear}-${endYear}`;
        }
    }

    // Validate date range
    function validateDates() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && startDate >= endDate) {
            endDateInput.setCustomValidity('End date must be after start date');
        } else {
            endDateInput.setCustomValidity('');
        }
    }

    startDateInput.addEventListener('change', function() {
        updateName();
        validateDates();
    });

    endDateInput.addEventListener('change', function() {
        updateName();
        validateDates();
    });

    // Set default dates if empty
    if (!startDateInput.value) {
        const currentYear = new Date().getFullYear();
        const nextYear = currentYear + 1;
        startDateInput.value = `${currentYear}-04-01`; // April 1st
        endDateInput.value = `${nextYear}-03-31`; // March 31st next year
        updateName();
    }
});
</script>
@endpush