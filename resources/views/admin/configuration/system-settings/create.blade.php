@extends('layouts.app')

@section('title', 'Add System Setting')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Add System Setting</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.index') }}">Configuration</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.system-settings.index') }}">System Settings</a></li>
                        <li class="breadcrumb-item active">Add Setting</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Add New System Setting</h5>
                    
                    <form action="{{ route('admin.configuration.system-settings.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="key" class="form-label">Setting Key <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('key') is-invalid @enderror" 
                                           id="key" name="key" value="{{ old('key') }}" required
                                           placeholder="e.g., app.maintenance_mode">
                                    <div class="form-text">Unique identifier for the setting (use dot notation)</div>
                                    @error('key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="label" class="form-label">Display Label <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('label') is-invalid @enderror" 
                                           id="label" name="label" value="{{ old('label') }}" required
                                           placeholder="e.g., Maintenance Mode">
                                    @error('label')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Data Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="string" {{ old('type') == 'string' ? 'selected' : '' }}>String</option>
                                        <option value="integer" {{ old('type') == 'integer' ? 'selected' : '' }}>Integer</option>
                                        <option value="decimal" {{ old('type') == 'decimal' ? 'selected' : '' }}>Decimal</option>
                                        <option value="boolean" {{ old('type') == 'boolean' ? 'selected' : '' }}>Boolean</option>
                                        <option value="json" {{ old('type') == 'json' ? 'selected' : '' }}>JSON</option>
                                    </select>
                                    @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('category') is-invalid @enderror" 
                                            id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="general" {{ old('category') == 'general' ? 'selected' : '' }}>General</option>
                                        <option value="academic" {{ old('category') == 'academic' ? 'selected' : '' }}>Academic</option>
                                        <option value="notification" {{ old('category') == 'notification' ? 'selected' : '' }}>Notification</option>
                                        <option value="security" {{ old('category') == 'security' ? 'selected' : '' }}>Security</option>
                                        <option value="system" {{ old('category') == 'system' ? 'selected' : '' }}>System</option>
                                        <option value="integration" {{ old('category') == 'integration' ? 'selected' : '' }}>Integration</option>
                                    </select>
                                    @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Brief description of what this setting controls">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="value" class="form-label">Default Value <span class="text-danger">*</span></label>
                            <div id="value-input-container">
                                <input type="text" class="form-control @error('value') is-invalid @enderror" 
                                       id="value" name="value" value="{{ old('value') }}" required>
                            </div>
                            @error('value')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" 
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                                    <div class="form-text">Lower numbers appear first</div>
                                    @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" 
                                           {{ old('is_public') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_public">
                                        Public Setting
                                    </label>
                                    <div class="form-text">Public settings can be accessed by non-admin users</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_editable" name="is_editable" value="1" 
                                           {{ old('is_editable', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_editable">
                                        Editable
                                    </label>
                                    <div class="form-text">Uncheck for system settings that shouldn't be modified</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.configuration.system-settings.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i> Save Setting
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
    const typeSelect = document.getElementById('type');
    const valueContainer = document.getElementById('value-input-container');
    const originalValue = document.getElementById('value').value;

    function updateValueInput() {
        const type = typeSelect.value;
        let inputHtml = '';

        switch(type) {
            case 'boolean':
                inputHtml = `
                    <select class="form-select" id="value" name="value" required>
                        <option value="1" ${originalValue == '1' ? 'selected' : ''}>Yes</option>
                        <option value="0" ${originalValue == '0' ? 'selected' : ''}>No</option>
                    </select>
                `;
                break;
            case 'integer':
                inputHtml = `<input type="number" class="form-control" id="value" name="value" value="${originalValue}" required>`;
                break;
            case 'decimal':
                inputHtml = `<input type="number" step="0.01" class="form-control" id="value" name="value" value="${originalValue}" required>`;
                break;
            case 'json':
                inputHtml = `
                    <textarea class="form-control" id="value" name="value" rows="5" required placeholder='{"key": "value"}'>${originalValue}</textarea>
                    <div class="form-text">Enter valid JSON format</div>
                `;
                break;
            default:
                inputHtml = `<input type="text" class="form-control" id="value" name="value" value="${originalValue}" required>`;
        }

        valueContainer.innerHTML = inputHtml;
    }

    typeSelect.addEventListener('change', updateValueInput);
    
    // Initialize on page load
    if (typeSelect.value) {
        updateValueInput();
    }
});
</script>
@endpush