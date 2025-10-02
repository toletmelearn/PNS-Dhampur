@extends('layouts.app')

@section('title', 'Teacher Profile - ' . $teacher->user->name)

@push('styles')
<style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 2rem;
        position: relative;
        overflow: hidden;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(50px, -50px);
    }

    .profile-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        object-fit: cover;
    }

    .info-section {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .section-title {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .info-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f3f4;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #4a5568;
        min-width: 150px;
    }

    .info-value {
        color: #2d3748;
        flex: 1;
    }

    .badge-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .stats-number {
        font-size: 2rem;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 0.5rem;
    }

    .stats-label {
        color: #718096;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .subject-tag {
        background: #f7fafc;
        border: 2px solid #e2e8f0;
        border-radius: 20px;
        padding: 0.5rem 1rem;
        margin: 0.25rem;
        display: inline-block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #4a5568;
    }

    .document-item {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .document-item a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }

    .document-item a:hover {
        text-decoration: underline;
    }

    .status-active {
        color: #38a169;
        background: #f0fff4;
        border: 1px solid #9ae6b4;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-inactive {
        color: #e53e3e;
        background: #fff5f5;
        border: 1px solid #feb2b2;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .class-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .class-card:hover {
        border-color: #667eea;
        box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Profile Header -->
    <div class="profile-card">
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    @if(isset($teacher->documents['photo']))
                        <img src="{{ Storage::url($teacher->documents['photo']) }}" 
                             alt="{{ $teacher->user->name }}" class="profile-avatar">
                    @else
                        <div class="profile-avatar d-flex align-items-center justify-content-center" 
                             style="background: rgba(255,255,255,0.2);">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <h2 class="mb-2">{{ $teacher->user->name }}</h2>
                    <p class="mb-2">{{ $teacher->qualification }}</p>
                    <p class="mb-3">
                        <i class="fas fa-envelope me-2"></i>{{ $teacher->user->email }}
                        <br>
                        <i class="fas fa-phone me-2"></i>{{ $teacher->user->phone }}
                    </p>
                    <span class="badge-custom">
                        {{ $teacher->experience_years }} Years Experience
                    </span>
                </div>
                <div class="col-md-3 text-end">
                    <div class="mb-2">
                        @if($teacher->user->status === 'active')
                            <span class="status-active">
                                <i class="fas fa-check-circle me-1"></i>Active
                            </span>
                        @else
                            <span class="status-inactive">
                                <i class="fas fa-times-circle me-1"></i>Inactive
                            </span>
                        @endif
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('teachers.edit', $teacher->id) }}" class="btn btn-primary-custom btn-sm">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                        <a href="{{ route('teachers.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number">{{ $teacher->classes->count() }}</div>
                <div class="stats-label">Classes Assigned</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number">{{ $teacher->subjects->count() }}</div>
                <div class="stats-label">Subjects Teaching</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number">₹{{ number_format($teacher->salary) }}</div>
                <div class="stats-label">Monthly Salary</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number">{{ $teacher->joining_date ? $teacher->joining_date->diffInDays(now()) : 0 }}</div>
                <div class="stats-label">Days with School</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Personal Information -->
        <div class="col-lg-6">
            <div class="info-section">
                <h4 class="section-title">
                    <i class="fas fa-user me-2"></i>Personal Information
                </h4>
                <div class="info-item">
                    <div class="info-label">Full Name:</div>
                    <div class="info-value">{{ $teacher->user->name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email:</div>
                    <div class="info-value">{{ $teacher->user->email }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone:</div>
                    <div class="info-value">{{ $teacher->user->phone }}</div>
                </div>
                @if($teacher->user->emergency_contact)
                <div class="info-item">
                    <div class="info-label">Emergency Contact:</div>
                    <div class="info-value">{{ $teacher->user->emergency_contact }}</div>
                </div>
                @endif
                @if($teacher->user->blood_group)
                <div class="info-item">
                    <div class="info-label">Blood Group:</div>
                    <div class="info-value">{{ $teacher->user->blood_group }}</div>
                </div>
                @endif
                @if($teacher->user->address)
                <div class="info-item">
                    <div class="info-label">Address:</div>
                    <div class="info-value">{{ $teacher->user->address }}</div>
                </div>
                @endif
                <div class="info-item">
                    <div class="info-label">Joined:</div>
                    <div class="info-value">{{ $teacher->user->created_at->format('M d, Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Professional Information -->
        <div class="col-lg-6">
            <div class="info-section">
                <h4 class="section-title">
                    <i class="fas fa-graduation-cap me-2"></i>Professional Information
                </h4>
                <div class="info-item">
                    <div class="info-label">Qualification:</div>
                    <div class="info-value">{{ $teacher->qualification }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Experience:</div>
                    <div class="info-value">{{ $teacher->experience_years }} Years</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Salary:</div>
                    <div class="info-value">₹{{ number_format($teacher->salary) }} per month</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Joining Date:</div>
                    <div class="info-value">{{ $teacher->joining_date ? $teacher->joining_date->format('M d, Y') : 'Not specified' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Employee ID:</div>
                    <div class="info-value">TCH-{{ str_pad($teacher->id, 4, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teaching Subjects -->
    @if($teacher->subjects->count() > 0)
    <div class="info-section">
        <h4 class="section-title">
            <i class="fas fa-book me-2"></i>Teaching Subjects
        </h4>
        <div class="row">
            @foreach($teacher->subjects as $subject)
            <div class="col-md-3 mb-2">
                <span class="subject-tag">{{ $subject->name }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Assigned Classes -->
    @if($teacher->classes->count() > 0)
    <div class="info-section">
        <h4 class="section-title">
            <i class="fas fa-chalkboard-teacher me-2"></i>Assigned Classes
        </h4>
        <div class="row">
            @foreach($teacher->classes as $class)
            <div class="col-md-4 mb-3">
                <div class="class-card">
                    <h6 class="mb-2">{{ $class->name }}</h6>
                    <p class="text-muted mb-2">{{ $class->section ?? 'No Section' }}</p>
                    <small class="text-muted">
                        <i class="fas fa-users me-1"></i>
                        Students: {{ $class->students->count() ?? 0 }}
                    </small>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Documents -->
    @if($teacher->documents && count($teacher->documents) > 0)
    <div class="info-section">
        <h4 class="section-title">
            <i class="fas fa-file-alt me-2"></i>Documents
        </h4>
        <div class="row">
            @foreach($teacher->documents as $type => $path)
            <div class="col-md-6 mb-3">
                <div class="document-item">
                    <div>
                        <i class="fas fa-file me-2"></i>
                        <strong>{{ ucfirst(str_replace('_', ' ', $type)) }}</strong>
                    </div>
                    <a href="{{ Storage::url($path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>View
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Teacher Availability -->
    @if($teacher->availability->count() > 0)
    <div class="info-section">
        <h4 class="section-title">
            <i class="fas fa-calendar-alt me-2"></i>Availability Schedule
        </h4>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teacher->availability as $availability)
                    <tr>
                        <td>{{ ucfirst($availability->day_of_week) }}</td>
                        <td>{{ $availability->start_time }}</td>
                        <td>{{ $availability->end_time }}</td>
                        <td>
                            @if($availability->is_available)
                                <span class="badge bg-success">Available</span>
                            @else
                                <span class="badge bg-danger">Not Available</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Recent Salary Records -->
    @if($teacher->salaries->count() > 0)
    <div class="info-section">
        <h4 class="section-title">
            <i class="fas fa-money-bill-wave me-2"></i>Recent Salary Records
        </h4>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Paid Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teacher->salaries->take(5) as $salary)
                    <tr>
                        <td>{{ $salary->month }}</td>
                        <td>₹{{ number_format($salary->amount) }}</td>
                        <td>
                            @if($salary->status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($salary->status === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @else
                                <span class="badge bg-danger">Overdue</span>
                            @endif
                        </td>
                        <td>{{ $salary->paid_date ? $salary->paid_date->format('M d, Y') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any interactive functionality here
    console.log('Teacher profile loaded for: {{ $teacher->user->name }}');
});
</script>
@endpush