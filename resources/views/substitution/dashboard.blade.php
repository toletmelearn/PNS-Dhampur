@extends('layouts.app')

@section('title', 'Teacher Substitution Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Teacher Substitution Dashboard</h1>
                    <p class="text-muted">Manage teacher absences and substitute assignments</p>
                </div>
                <div>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createSubstitutionModal">
                        <i class="fas fa-plus"></i> New Substitution
                    </button>
                    <button class="btn btn-outline-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Today's Substitutions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['today_total'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Assignments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_count'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Emergency Requests
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['emergency_count'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Success Rate
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['success_rate'] ?? 0 }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Today's Substitutions -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Today's Substitutions</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" onclick="exportTodaySubstitutions()">Export to Excel</a>
                            <a class="dropdown-item" href="#" onclick="printTodaySubstitutions()">Print</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="todaySubstitutionsTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Original Teacher</th>
                                    <th>Substitute</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todaySubstitutions as $substitution)
                                <tr>
                                    <td>
                                        <small class="text-muted">Period {{ $substitution->period_number }}</small><br>
                                        {{ date('H:i', strtotime($substitution->start_time)) }} - {{ date('H:i', strtotime($substitution->end_time)) }}
                                    </td>
                                    <td>{{ $substitution->class->name ?? 'N/A' }}</td>
                                    <td>{{ $substitution->subject->name ?? 'N/A' }}</td>
                                    <td>{{ $substitution->originalTeacher->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($substitution->substituteTeacher)
                                            {{ $substitution->substituteTeacher->name }}
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $substitution->status === 'completed' ? 'success' : ($substitution->status === 'confirmed' ? 'primary' : ($substitution->status === 'pending' ? 'warning' : 'secondary')) }}">
                                            {{ ucfirst($substitution->status) }}
                                        </span>
                                        @if($substitution->is_emergency)
                                            <span class="badge badge-danger ms-1">Emergency</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewSubstitution({{ $substitution->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($substitution->status === 'pending' && !$substitution->substituteTeacher)
                                                <button class="btn btn-outline-success" onclick="assignSubstitute({{ $substitution->id }})">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                            @endif
                                            @if(in_array($substitution->status, ['pending', 'confirmed']))
                                                <button class="btn btn-outline-danger" onclick="cancelSubstitution({{ $substitution->id }})">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No substitutions scheduled for today</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Emergency Requests -->
        <div class="col-lg-4">
            <!-- Emergency Requests -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">Emergency Requests</h6>
                </div>
                <div class="card-body">
                    @forelse($emergencySubstitutions as $emergency)
                    <div class="alert alert-danger" role="alert">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $emergency->class->name ?? 'N/A' }} - {{ $emergency->subject->name ?? 'N/A' }}</strong><br>
                                <small>{{ date('H:i', strtotime($emergency->start_time)) }} - {{ date('H:i', strtotime($emergency->end_time)) }}</small><br>
                                <small class="text-muted">{{ $emergency->reason }}</small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" onclick="handleEmergency({{ $emergency->id }})">
                                Handle
                            </button>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center">No emergency requests</p>
                    @endforelse
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createSubstitutionModal">
                            <i class="fas fa-plus"></i> Create Substitution
                        </button>
                        <button class="btn btn-outline-primary" onclick="findAvailableTeachers()">
                            <i class="fas fa-search"></i> Find Available Teachers
                        </button>
                        <button class="btn btn-outline-secondary" onclick="viewAllSubstitutions()">
                            <i class="fas fa-list"></i> View All Substitutions
                        </button>
                        <button class="btn btn-outline-info" onclick="generateReport()">
                            <i class="fas fa-chart-bar"></i> Generate Report
                        </button>
                    </div>
                </div>
            </div>

            <!-- Upcoming Substitutions -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Upcoming (Next 7 Days)</h6>
                </div>
                <div class="card-body">
                    @forelse($upcomingSubstitutions->take(5) as $upcoming)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>{{ date('M j', strtotime($upcoming->substitution_date)) }}</strong><br>
                            <small>{{ $upcoming->class->name ?? 'N/A' }} - {{ $upcoming->subject->name ?? 'N/A' }}</small>
                        </div>
                        <span class="badge badge-{{ $upcoming->substituteTeacher ? 'success' : 'warning' }}">
                            {{ $upcoming->substituteTeacher ? 'Assigned' : 'Pending' }}
                        </span>
                    </div>
                    @empty
                    <p class="text-muted text-center">No upcoming substitutions</p>
                    @endforelse
                    
                    @if($upcomingSubstitutions->count() > 5)
                    <div class="text-center mt-3">
                        <a href="#" onclick="viewAllSubstitutions()" class="btn btn-sm btn-outline-primary">
                            View All ({{ $upcomingSubstitutions->count() }})
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('substitution.modals')

@endsection

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.badge-pending {
    background-color: #f6c23e;
    color: #fff;
}
.badge-confirmed {
    background-color: #4e73df;
    color: #fff;
}
.badge-completed {
    background-color: #1cc88a;
    color: #fff;
}
.badge-cancelled {
    background-color: #858796;
    color: #fff;
}

.card {
    transition: all 0.3s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.alert {
    border-radius: 0.5rem;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
</style>
@endpush

@push('scripts')
<script>
// Auto-refresh dashboard every 5 minutes
setInterval(refreshDashboard, 300000);

function refreshDashboard() {
    location.reload();
}

function viewSubstitution(id) {
    // Load substitution details in modal
    fetch(`/substitutions/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSubstitutionDetails(data.substitution);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading substitution details', 'danger');
        });
}

function assignSubstitute(id) {
    // Show assign substitute modal
    $('#assignSubstituteModal').modal('show');
    $('#assignSubstituteModal').data('substitution-id', id);
    loadAvailableTeachers(id);
}

function handleEmergency(id) {
    // Handle emergency substitution
    if (confirm('This will attempt to auto-assign an available teacher. Continue?')) {
        fetch(`/substitutions/${id}/auto-assign`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                refreshDashboard();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error handling emergency request', 'danger');
        });
    }
}

function cancelSubstitution(id) {
    if (confirm('Are you sure you want to cancel this substitution?')) {
        fetch(`/substitutions/${id}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                refreshDashboard();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error cancelling substitution', 'danger');
        });
    }
}

function findAvailableTeachers() {
    $('#findTeachersModal').modal('show');
}

function viewAllSubstitutions() {
    window.location.href = '/substitutions';
}

function generateReport() {
    $('#reportModal').modal('show');
}

function exportTodaySubstitutions() {
    window.location.href = '/substitutions/export?date=' + new Date().toISOString().split('T')[0];
}

function printTodaySubstitutions() {
    window.print();
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const alertContainer = document.getElementById('alert-container') || document.body;
    alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = alertContainer.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

function showSubstitutionDetails(substitution) {
    // Populate and show substitution details modal
    const modal = $('#substitutionDetailsModal');
    modal.find('.modal-body').html(`
        <div class="row">
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <p><strong>Date:</strong> ${substitution.substitution_date}</p>
                <p><strong>Time:</strong> ${substitution.start_time} - ${substitution.end_time}</p>
                <p><strong>Period:</strong> ${substitution.period_number}</p>
                <p><strong>Class:</strong> ${substitution.class?.name || 'N/A'}</p>
                <p><strong>Subject:</strong> ${substitution.subject?.name || 'N/A'}</p>
            </div>
            <div class="col-md-6">
                <h6>Teachers</h6>
                <p><strong>Original:</strong> ${substitution.original_teacher?.name || 'N/A'}</p>
                <p><strong>Substitute:</strong> ${substitution.substitute_teacher?.name || 'Not assigned'}</p>
                <p><strong>Status:</strong> <span class="badge badge-${substitution.status}">${substitution.status}</span></p>
                ${substitution.is_emergency ? '<p><span class="badge badge-danger">Emergency</span></p>' : ''}
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Reason</h6>
                <p>${substitution.reason || 'N/A'}</p>
                ${substitution.notes ? `<h6>Notes</h6><p>${substitution.notes}</p>` : ''}
                ${substitution.preparation_materials ? `<h6>Preparation Materials</h6><p>${substitution.preparation_materials}</p>` : ''}
            </div>
        </div>
    `);
    modal.modal('show');
}

function loadAvailableTeachers(substitutionId) {
    // This would load available teachers for the specific substitution
    // Implementation depends on the substitution details
}
</script>
@endpush