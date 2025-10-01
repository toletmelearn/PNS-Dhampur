@extends('layouts.app')

@section('title', 'Class Teacher Permissions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Class Teacher Permissions</h3>
                    <div>
                        @can('manage-permissions')
                            <a href="{{ route('class-teacher-permissions.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Grant Permission
                            </a>
                        @endcan
                        <a href="{{ route('class-teacher-permissions.audit-trail') }}" class="btn btn-info">
                            <i class="fas fa-history"></i> Audit Trail
                        </a>
                        <a href="{{ route('class-teacher-permissions.dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Teacher</label>
                            <select name="teacher_id" class="form-select">
                                <option value="">All Teachers</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select">
                                <option value="">All Classes</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select">
                                <option value="">All Subjects</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Academic Year</label>
                            <select name="academic_year" class="form-select">
                                <option value="">All Years</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year }}" {{ request('academic_year') == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Teacher name/email" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('class-teacher-permissions.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Permissions Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Teacher</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Academic Year</th>
                                    <th>Permissions</th>
                                    <th>Valid Period</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permissions as $permission)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm me-2">
                                                    <div class="avatar-title bg-primary rounded-circle">
                                                        {{ substr($permission->teacher->name, 0, 1) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <strong>{{ $permission->teacher->name }}</strong><br>
                                                    <small class="text-muted">{{ $permission->teacher->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $permission->class->name }}</span>
                                        </td>
                                        <td>
                                            @if($permission->subject)
                                                <span class="badge bg-success">{{ $permission->subject->name }}</span>
                                            @else
                                                <span class="badge bg-secondary">All Subjects</span>
                                            @endif
                                        </td>
                                        <td>{{ $permission->academic_year }}</td>
                                        <td>
                                            <div class="permission-badges">
                                                @if($permission->can_view_records)
                                                    <span class="badge bg-primary mb-1">View</span>
                                                @endif
                                                @if($permission->can_edit_records)
                                                    <span class="badge bg-warning mb-1">Edit</span>
                                                @endif
                                                @if($permission->can_add_records)
                                                    <span class="badge bg-success mb-1">Add</span>
                                                @endif
                                                @if($permission->can_delete_records)
                                                    <span class="badge bg-danger mb-1">Delete</span>
                                                @endif
                                                @if($permission->can_export_reports)
                                                    <span class="badge bg-info mb-1">Export</span>
                                                @endif
                                                @if($permission->can_approve_corrections)
                                                    <span class="badge bg-purple mb-1">Approve</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <small>
                                                <strong>From:</strong> {{ $permission->valid_from->format('d/m/Y') }}<br>
                                                <strong>Until:</strong> {{ $permission->valid_until ? $permission->valid_until->format('d/m/Y') : 'Indefinite' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($permission->is_active && $permission->isCurrentlyValid())
                                                <span class="badge bg-success">Active</span>
                                            @elseif($permission->is_active && !$permission->isCurrentlyValid())
                                                <span class="badge bg-warning">Inactive (Date)</span>
                                            @else
                                                <span class="badge bg-danger">Revoked</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('class-teacher-permissions.show', $permission) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('manage-permissions')
                                                    @if($permission->is_active)
                                                        <a href="{{ route('class-teacher-permissions.edit', $permission) }}" 
                                                           class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="revokePermission({{ $permission->id }})" title="Revoke">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-users fa-3x mb-3"></i>
                                                <p>No permissions found</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            Showing {{ $permissions->firstItem() ?? 0 }} to {{ $permissions->lastItem() ?? 0 }} 
                            of {{ $permissions->total() }} permissions
                        </div>
                        {{ $permissions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revoke Permission Modal -->
<div class="modal fade" id="revokeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Revoke Permission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="revokeForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Are you sure you want to revoke this permission? This action cannot be undone.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Revocation <span class="text-danger">*</span></label>
                        <textarea name="revocation_reason" class="form-control" rows="3" required 
                                  placeholder="Please provide a reason for revoking this permission..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Revoke Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.permission-badges .badge {
    font-size: 0.7em;
    margin-right: 2px;
}

.avatar-sm {
    width: 32px;
    height: 32px;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
}

.bg-purple {
    background-color: #6f42c1 !important;
}
</style>
@endpush

@push('scripts')
<script>
function revokePermission(permissionId) {
    const modal = new bootstrap.Modal(document.getElementById('revokeModal'));
    const form = document.getElementById('revokeForm');
    form.action = `/class-teacher-permissions/${permissionId}/revoke`;
    modal.show();
}

// Auto-submit form on filter change
document.querySelectorAll('select[name="teacher_id"], select[name="class_id"], select[name="subject_id"], select[name="academic_year"]').forEach(select => {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>
@endpush