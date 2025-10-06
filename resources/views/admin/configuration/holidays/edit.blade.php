@extends('layouts.app')

@section('title', 'Edit Holiday')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Edit Holiday</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.index') }}">Configuration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.holidays.index') }}">Holidays</a></li>
                        <li class="breadcrumb-item active">Edit Holiday</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Edit Holiday: {{ $holiday->name }}</h5>
                        @if($holiday->is_recurring)
                            <span class="badge bg-info">Recurring Holiday</span>
                        @endif
                    </div>
                    
                    <form action="{{ route('admin.configuration.holidays.update', $holiday) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Holiday Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $holiday->name) }}" required
                                           placeholder="e.g., Diwali, Summer Vacation">
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="academic_year_id" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                    <select class="form-select @error('academic_year_id') is-invalid @enderror" 
                                            id="academic_year_id" name="academic_year_id" required>
                                        <option value="">Select Academic Year</option>
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->id }}" 
                                                {{ old('academic_year_id', $holiday->academic_year_id) == $year->id ? 'selected' : '' }}>
                                                {{ $year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('academic_year_id')
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
                                           id="start_date" name="start_date" 
                                           value="{{ old('start_date', $holiday->start_date->format('Y-m-d')) }}" required>
                                    @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                           id="end_date" name="end_date" 
                                           value="{{ old('end_date', $holiday->end_date->format('Y-m-d')) }}" required>
                                    @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        @foreach(\App\Models\Holiday::TYPES as $key => $type)
                                            <option value="{{ $key }}" 
                                                {{ old('type', $holiday->type) == $key ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category') is-invalid @enderror" 
                                            id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        @foreach(\App\Models\Holiday::CATEGORIES as $key => $category)
                                            <option value="{{ $key }}" 
                                                {{ old('category', $holiday->category) == $key ? 'selected' : '' }}>
                                                {{ $category }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" 
                                           id="color" name="color" value="{{ old('color', $holiday->color) }}" title="Choose color">
                                    <div class="form-text">Color for calendar display</div>
                                    @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Brief description of the holiday">{{ old('description', $holiday->description) }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Recurrence Settings -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" value="1" 
                                           {{ old('is_recurring', $holiday->is_recurring) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_recurring">
                                        <h6 class="mb-0">Recurring Holiday</h6>
                                    </label>
                                </div>
                            </div>
                            <div class="card-body" id="recurrenceSettings" 
                                 style="display: {{ old('is_recurring', $holiday->is_recurring) ? 'block' : 'none' }};">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="recurrence_pattern" class="form-label">Recurrence Pattern</label>
                                            <select class="form-select @error('recurrence_pattern') is-invalid @enderror" 
                                                    id="recurrence_pattern" name="recurrence_pattern">
                                                <option value="">Select Pattern</option>
                                                @foreach(\App\Models\Holiday::RECURRENCE_PATTERNS as $key => $pattern)
                                                    <option value="{{ $key }}" 
                                                        {{ old('recurrence_pattern', $holiday->recurrence_pattern) == $key ? 'selected' : '' }}>
                                                        {{ $pattern }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('recurrence_pattern')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                @if($holiday->is_recurring)
                                <div class="alert alert-warning">
                                    <i class="mdi mdi-alert me-1"></i>
                                    <strong>Warning:</strong> Changing recurrence settings will affect future instances of this holiday. 
                                    Past instances will remain unchanged.
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Holiday Statistics -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">Holiday Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h5 class="text-primary">{{ $holiday->duration }} {{ Str::plural('day', $holiday->duration) }}</h5>
                                            <p class="text-muted mb-0">Duration</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h5 class="text-info">{{ $holiday->formatted_dates }}</h5>
                                            <p class="text-muted mb-0">Date Range</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h5 class="text-{{ $holiday->is_active ? 'success' : 'danger' }}">
                                                {{ $holiday->is_active ? 'Active' : 'Inactive' }}
                                            </h5>
                                            <p class="text-muted mb-0">Status</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h5 class="text-secondary">{{ ucfirst($holiday->type) }}</h5>
                                            <p class="text-muted mb-0">Type</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', $holiday->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                    <div class="form-text">Active holidays are displayed in calendars and reports</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.configuration.holidays.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i> Back
                            </a>
                            <div>
                                @if($holiday->is_recurring)
                                <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#recurrenceModal">
                                    <i class="mdi mdi-repeat me-1"></i> View Recurrence
                                </button>
                                @endif
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save me-1"></i> Update Holiday
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recurrence Modal -->
@if($holiday->is_recurring)
<div class="modal fade" id="recurrenceModal" tabindex="-1" aria-labelledby="recurrenceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recurrenceModalLabel">Recurrence Details: {{ $holiday->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Pattern:</strong> {{ \App\Models\Holiday::RECURRENCE_PATTERNS[$holiday->recurrence_pattern] ?? 'N/A' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Original Date:</strong> {{ $holiday->formatted_dates }}
                    </div>
                </div>
                
                <h6>Future Instances:</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Academic Year</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $futureInstances = $holiday->generateRecurringInstances(5); // Generate next 5 instances
                            @endphp
                            @forelse($futureInstances as $instance)
                            <tr>
                                <td>{{ $instance['academic_year'] ?? 'TBD' }}</td>
                                <td>{{ $instance['start_date'] }}</td>
                                <td>{{ $instance['end_date'] }}</td>
                                <td>
                                    <span class="badge bg-secondary">Future</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No future instances found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="mdi mdi-information me-1"></i>
                    Future instances will be automatically created when new academic years are added.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const isRecurringCheckbox = document.getElementById('is_recurring');
    const recurrenceSettings = document.getElementById('recurrenceSettings');

    // Validate date range
    function validateDates() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && startDate > endDate) {
            endDateInput.setCustomValidity('End date must be on or after start date');
        } else {
            endDateInput.setCustomValidity('');
        }
    }

    startDateInput.addEventListener('change', validateDates);
    endDateInput.addEventListener('change', validateDates);

    // Toggle recurrence settings
    isRecurringCheckbox.addEventListener('change', function() {
        if (this.checked) {
            recurrenceSettings.style.display = 'block';
            document.getElementById('recurrence_pattern').required = true;
        } else {
            recurrenceSettings.style.display = 'none';
            document.getElementById('recurrence_pattern').required = false;
        }
    });

    // Initialize recurrence settings visibility
    if (isRecurringCheckbox.checked) {
        document.getElementById('recurrence_pattern').required = true;
    }

    // Color picker enhancement
    const colorInput = document.getElementById('color');
    const predefinedColors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1', '#e83e8c', '#fd7e14'];
    
    // Create color palette
    const colorPalette = document.createElement('div');
    colorPalette.className = 'mt-2';
    colorPalette.innerHTML = '<small class="text-muted">Quick colors:</small><br>';
    
    predefinedColors.forEach(color => {
        const colorButton = document.createElement('button');
        colorButton.type = 'button';
        colorButton.className = 'btn btn-sm me-1 mt-1';
        colorButton.style.backgroundColor = color;
        colorButton.style.width = '30px';
        colorButton.style.height = '30px';
        colorButton.style.border = '2px solid #dee2e6';
        colorButton.onclick = () => {
            colorInput.value = color;
        };
        colorPalette.appendChild(colorButton);
    });
    
    colorInput.parentNode.appendChild(colorPalette);

    // Form change detection
    let originalFormData = new FormData(document.querySelector('form'));
    let hasChanges = false;

    document.querySelector('form').addEventListener('input', function() {
        hasChanges = true;
    });

    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    document.querySelector('form').addEventListener('submit', function() {
        hasChanges = false;
    });
});
</script>
@endpush