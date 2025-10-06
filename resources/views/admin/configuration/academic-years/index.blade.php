@extends('layouts.app')

@section('title', 'Academic Years')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Academic Years</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.configuration.index') }}">Configuration</a></li>
                        <li class="breadcrumb-item active">Academic Years</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <h5 class="card-title">Academic Years Management</h5>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end">
                                <a href="{{ route('admin.configuration.academic-years.create') }}" class="btn btn-primary">
                                    <i class="mdi mdi-plus me-1"></i> Add Academic Year
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Academic Year</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Holidays</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($academicYears as $year)
                                <tr class="{{ $year->is_current ? 'table-success' : '' }}">
                                    <td>
                                        <div>
                                            <strong>{{ $year->name }}</strong>
                                            @if($year->is_current)
                                            <span class="badge bg-success ms-2">Current</span>
                                            @endif
                                            @if($year->description)
                                            <br>
                                            <small class="text-muted">{{ $year->description }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $year->start_date->format('M d, Y') }}</strong> to 
                                            <strong>{{ $year->end_date->format('M d, Y') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $year->start_date->diffInDays($year->end_date) }} days</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($year->is_active)
                                            @if($year->isInSession())
                                                <span class="badge bg-success">In Session</span>
                                            @elseif($year->start_date->isFuture())
                                                <span class="badge bg-info">Upcoming</span>
                                            @else
                                                <span class="badge bg-secondary">Completed</span>
                                            @endif
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($year->isInSession())
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar" style="width: {{ $year->getProgress() }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ number_format($year->getProgress(), 1) }}% complete</small>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $year->holidays->count() }} holidays</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.configuration.academic-years.show', $year) }}" 
                                               class="btn btn-outline-info" title="View Details">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.configuration.academic-years.edit', $year) }}" 
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="mdi mdi-pencil"></i>
                                            </a>
                                            @if(!$year->is_current)
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="setCurrent({{ $year->id }})" title="Set as Current">
                                                <i class="mdi mdi-check-circle"></i>
                                            </button>
                                            @endif
                                            @if(!$year->is_current && $year->holidays->count() == 0)
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="confirmDelete({{ $year->id }}, '{{ $year->name }}')" title="Delete">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="mdi mdi-calendar-range font-size-24 d-block mb-2"></i>
                                        No academic years found. <a href="{{ route('admin.configuration.academic-years.create') }}">Add the first one</a>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($academicYears->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $academicYears->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Current Academic Year Summary -->
    @if($currentYear)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Current Academic Year Summary</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary">{{ $currentYear->name }}</h4>
                                <p class="text-muted mb-0">Academic Year</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success">{{ $currentYear->getRemainingDays() }}</h4>
                                <p class="text-muted mb-0">Days Remaining</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info">{{ $currentYear->holidays->where('is_active', true)->count() }}</h4>
                                <p class="text-muted mb-0">Active Holidays</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-warning">{{ number_format($currentYear->getProgress(), 1) }}%</h4>
                                <p class="text-muted mb-0">Progress</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set current academic year
    window.setCurrent = function(yearId) {
        if (confirm('Are you sure you want to set this as the current academic year? This will deactivate the current year.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/configuration/academic-years/${yearId}/set-current`;
            form.innerHTML = `
                @csrf
                @method('PATCH')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };

    // Delete confirmation
    window.confirmDelete = function(yearId, yearName) {
        if (confirm(`Are you sure you want to delete the academic year "${yearName}"? This action cannot be undone.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/configuration/academic-years/${yearId}`;
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };
});
</script>
@endpush