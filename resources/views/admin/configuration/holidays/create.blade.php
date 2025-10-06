@extends('layouts.app')

@section('title', 'Add Holiday')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Add Holiday</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.index') }}">Configuration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.holidays.index') }}">Holidays</a></li>
                        <li class="breadcrumb-item active">Add Holiday</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Add New Holiday</h5>
                    
                    <form action="{{ route('admin.configuration.holidays.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Holiday Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required
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
                                            <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
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
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        @foreach(\App\Models\Holiday::TYPES as $key => $type)
                                            <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
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
                                            <option value="{{ $key }}" {{ old('category') == $key ? 'selected' : '' }}>
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
                                           id="color" name="color" value="{{ old('color', '#007bff') }}" title="Choose color">
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
                                      placeholder="Brief description of the holiday">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Recurrence Settings -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" value="1" 
                                           {{ old('is_recurring') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_recurring">
                                        <h6 class="mb-0">Recurring Holiday</h6>
                                    </label>
                                </div>
                            </div>
                            <div class="card-body" id="recurrenceSettings" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="recurrence_pattern" class="form-label">Recurrence Pattern</label>
                                            <select class="form-select @error('recurrence_pattern') is-invalid @enderror" 
                                                    id="recurrence_pattern" name="recurrence_pattern">
                                                <option value="">Select Pattern</option>
                                                @foreach(\App\Models\Holiday::RECURRENCE_PATTERNS as $key => $pattern)
                                                    <option value="{{ $key }}" {{ old('recurrence_pattern') == $key ? 'selected' : '' }}>
                                                        {{ $pattern }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('recurrence_pattern')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="recurrence_end_date" class="form-label">Recurrence End Date</label>
                                            <input type="date" class="form-control @error('recurrence_end_date') is-invalid @enderror" 
                                                   id="recurrence_end_date" name="recurrence_end_date" value="{{ old('recurrence_end_date') }}">
                                            <div class="form-text">Leave empty for indefinite recurrence</div>
                                            @error('recurrence_end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="mdi mdi-information me-1"></i>
                                    <strong>Recurrence Note:</strong> This holiday will be automatically created for future academic years based on the selected pattern.
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', true) ? 'checked' : '' }}>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i> Save Holiday
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
    const isRecurringCheckbox = document.getElementById('is_recurring');
    const recurrenceSettings = document.getElementById('recurrenceSettings');

    // Auto-set end date to start date if not set
    startDateInput.addEventListener('change', function() {
        if (!endDateInput.value) {
            endDateInput.value = startDateInput.value;
        }
        validateDates();
    });

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

    endDateInput.addEventListener('change', validateDates);

    // Toggle recurrence settings
    isRecurringCheckbox.addEventListener('change', function() {
        if (this.checked) {
            recurrenceSettings.style.display = 'block';
            document.getElementById('recurrence_pattern').required = true;
        } else {
            recurrenceSettings.style.display = 'none';
            document.getElementById('recurrence_pattern').required = false;
            document.getElementById('recurrence_pattern').value = '';
            document.getElementById('recurrence_end_date').value = '';
        }
    });

    // Initialize recurrence settings visibility
    if (isRecurringCheckbox.checked) {
        recurrenceSettings.style.display = 'block';
        document.getElementById('recurrence_pattern').required = true;
    }

    // Academic year change handler
    document.getElementById('academic_year_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            // You could fetch academic year dates and suggest holiday dates
            // This is a placeholder for future enhancement
        }
    });

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
});
</script>
@endpush