<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Setting</th>
                <th>Value</th>
                <th>Type</th>
                <th>Category</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($settings as $setting)
            <tr>
                <td>
                    <div>
                        <strong>{{ $setting->label }}</strong>
                        @if($setting->description)
                        <br>
                        <small class="text-muted">{{ $setting->description }}</small>
                        @endif
                    </div>
                </td>
                <td>
                    @if($setting->type === 'boolean')
                        <span class="badge bg-{{ $setting->value ? 'success' : 'danger' }}">
                            {{ $setting->value ? 'Yes' : 'No' }}
                        </span>
                    @elseif($setting->type === 'json')
                        <code class="small">{{ Str::limit($setting->value, 50) }}</code>
                    @else
                        {{ Str::limit($setting->value, 50) }}
                    @endif
                </td>
                <td>
                    <span class="badge bg-info">{{ ucfirst($setting->type) }}</span>
                </td>
                <td>
                    <span class="badge bg-secondary">{{ ucfirst($setting->category) }}</span>
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        @if($setting->is_editable)
                        <button type="button" class="btn btn-outline-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editSettingModal"
                                data-id="{{ $setting->id }}"
                                data-key="{{ $setting->key }}"
                                data-label="{{ $setting->label }}"
                                data-description="{{ $setting->description }}"
                                data-value="{{ $setting->value }}"
                                data-type="{{ $setting->type }}">
                            <i class="mdi mdi-pencil"></i>
                        </button>
                        @else
                        <button type="button" class="btn btn-outline-secondary" disabled title="System setting - not editable">
                            <i class="mdi mdi-lock"></i>
                        </button>
                        @endif
                        
                        @if($setting->is_editable)
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="confirmDelete({{ $setting->id }}, '{{ $setting->key }}')">
                            <i class="mdi mdi-delete"></i>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="mdi mdi-cog-outline font-size-24 d-block mb-2"></i>
                    No settings found in this category.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>