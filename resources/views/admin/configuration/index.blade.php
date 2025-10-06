@extends('layouts.app')

@section('title', 'Configuration Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Configuration Management</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Configuration</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- System Settings Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-primary rounded">
                                    <i class="mdi mdi-cog font-size-18"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">System Settings</h5>
                            <p class="text-muted mb-0">{{ $settingsCount }} settings configured</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('admin.configuration.system-settings.index') }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-cog me-1"></i> Manage Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Years Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-success rounded">
                                    <i class="mdi mdi-calendar-range font-size-18"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">Academic Years</h5>
                            <p class="text-muted mb-0">{{ $academicYearsCount }} years configured</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('admin.configuration.academic-years.index') }}" class="btn btn-success btn-sm">
                            <i class="mdi mdi-calendar-range me-1"></i> Manage Years
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holidays Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-warning rounded">
                                    <i class="mdi mdi-calendar-star font-size-18"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">Holidays</h5>
                            <p class="text-muted mb-0">{{ $holidaysCount }} holidays configured</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('admin.configuration.holidays.index') }}" class="btn btn-warning btn-sm">
                            <i class="mdi mdi-calendar-star me-1"></i> Manage Holidays
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Templates Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-info rounded">
                                    <i class="mdi mdi-email-outline font-size-18"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">Notification Templates</h5>
                            <p class="text-muted mb-0">{{ $templatesCount }} templates configured</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('admin.configuration.notification-templates.index') }}" class="btn btn-info btn-sm">
                            <i class="mdi mdi-email-outline me-1"></i> Manage Templates
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Academic Year Info -->
    @if($currentAcademicYear)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Current Academic Year</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Academic Year</label>
                                <p class="text-muted">{{ $currentAcademicYear->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <p class="text-muted">{{ $currentAcademicYear->start_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <p class="text-muted">{{ $currentAcademicYear->end_date->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Progress</label>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $currentAcademicYear->getProgress() }}%"></div>
                                </div>
                                <small class="text-muted">{{ number_format($currentAcademicYear->getProgress(), 1) }}% complete</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Upcoming Holidays -->
    @if($upcomingHolidays->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Upcoming Holidays</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Holiday</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Days Remaining</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcomingHolidays as $holiday)
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: {{ $holiday->color }}">{{ $holiday->name }}</span>
                                    </td>
                                    <td>{{ $holiday->start_date->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst($holiday->type) }}</span>
                                    </td>
                                    <td>{{ $holiday->start_date->diffInDays(now()) }} days</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="{{ route('admin.configuration.system-settings.create') }}" class="btn btn-outline-primary btn-block">
                                <i class="mdi mdi-plus me-1"></i> Add System Setting
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.configuration.academic-years.create') }}" class="btn btn-outline-success btn-block">
                                <i class="mdi mdi-plus me-1"></i> Add Academic Year
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.configuration.holidays.create') }}" class="btn btn-outline-warning btn-block">
                                <i class="mdi mdi-plus me-1"></i> Add Holiday
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.configuration.notification-templates.create') }}" class="btn btn-outline-info btn-block">
                                <i class="mdi mdi-plus me-1"></i> Add Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection