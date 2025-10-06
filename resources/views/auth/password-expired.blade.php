@extends('layouts.app')

@section('title', 'Password Expired')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        Password Expired
                    </h4>
                </div>

                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-clock"></i> Your password has expired</h5>
                        <p class="mb-0">
                            For security reasons, your password has expired and must be changed before you can continue using the system.
                        </p>
                    </div>

                    <div class="text-center">
                        <p class="lead">Please change your password to continue.</p>
                        <a href="{{ route('password.change') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-key"></i>
                            Change Password Now
                        </a>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-shield-alt"></i> Security Benefits</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Protects your account from unauthorized access</li>
                                <li><i class="fas fa-check text-success"></i> Ensures compliance with security policies</li>
                                <li><i class="fas fa-check text-success"></i> Keeps your data safe</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle"></i> Need Help?</h6>
                            <p class="small text-muted">
                                If you're having trouble changing your password or have forgotten your current password, 
                                please contact your system administrator for assistance.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-center">
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection