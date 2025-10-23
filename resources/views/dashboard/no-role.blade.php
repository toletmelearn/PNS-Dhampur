@extends('layouts.app')

@section('title', 'No Role Assigned')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">
                        <i class="fas fa-user-shield text-warning me-2"></i>
                        No Role Assigned
                    </h4>
                    <p class="text-muted">Your account is active but does not have a primary role yet. Roles control access to specific dashboards and features.</p>
                    <ul>
                        <li>Contact an administrator to assign you a role (e.g., Admin, Principal, Teacher, Student).</li>
                        <li>If you believe this is an error, try logging out and back in, or clear your session.</li>
                    </ul>
                    <div class="mt-4 d-flex gap-2">
                        <a href="{{ route('dashboard.redirect') }}" class="btn btn-primary">
                            <i class="fas fa-redo me-1"></i> Retry Redirect
                        </a>
                        <a href="{{ route('logout') }}" class="btn btn-outline-secondary" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <strong>Tip:</strong> Admins can assign roles via Users management > Edit user > Roles.
            </div>
        </div>
    </div>
</div>
@endsection
