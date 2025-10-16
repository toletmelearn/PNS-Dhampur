@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col">
                <h1 class="h3">Welcome, {{ Auth::user()->name ?? 'Super Admin' }}</h1>
                <p class="text-muted mb-0">System overview and quick actions</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <h3>{{ $stats['total_users'] ?? 0 }}</h3>
                    <div>Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #34d399 0%, #059669 100%);">
                    <h3>{{ $stats['active_schools'] ?? 0 }}</h3>
                    <div>Active Schools</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <h3>{{ $stats['pending_approvals'] ?? 0 }}</h3>
                    <div>Pending Approvals</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #10b981 0%, #22c55e 100%);">
                    <h3>{{ $stats['attendance_today'] ?? 0 }}</h3>
                    <div>Attendance Today</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card p-3">
                    <h5 class="mb-3">User Management</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-primary btn-sm"><i class="fas fa-users me-1"></i> Manage Users</a>
                        <a href="{{ route('class-teacher-permissions.index') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-shield me-1"></i> Permissions</a>
                        <a href="{{ route('teacher-documents.index') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-folder-open me-1"></i> Teacher Docs</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3">
                    <h5 class="mb-3">School Management</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('classes.index') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-school me-1"></i> Classes</a>
                        <a href="{{ route('subjects.index') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-book me-1"></i> Subjects</a>
                        <a href="{{ route('attendance.index') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-fingerprint me-1"></i> Attendance</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Administration -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card p-3">
                    <h5 class="mb-3">System Administration</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cogs me-1"></i> System Settings</a>
                        <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-bell me-1"></i> Notifications</a>
                        <a href="{{ route('api.dashboard.stats') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-line me-1"></i> API: Dashboard Stats</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports & Analytics -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card p-3">
                    <h5 class="mb-3">Reports & Analytics</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('fees.index') }}" class="btn btn-outline-info btn-sm"><i class="fas fa-file-invoice-dollar me-1"></i> Fees</a>
                        <a href="{{ route('exams.index') }}" class="btn btn-outline-info btn-sm"><i class="fas fa-file-alt me-1"></i> Exams</a>
                        <a href="{{ route('attendance.index') }}" class="btn btn-outline-info btn-sm"><i class="fas fa-user-check me-1"></i> Attendance Analytics</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection