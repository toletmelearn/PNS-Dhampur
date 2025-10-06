@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>Change Password
                        @if($passwordResetRequired)
                            <span class="badge bg-danger ms-2">Required</span>
                        @elseif($isExpired)
                            <span class="badge bg-warning ms-2">Expired</span>
                        @elseif($isExpiringSoon)
                            <span class="badge bg-info ms-2">Expiring Soon</span>
                        @endif
                    </h4>
                </div>

                <div class="card-body">
                    @if($passwordResetRequired)
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Password Reset Required:</strong> You must change your password to continue using the system.
                        </div>
                    @elseif($isExpired)
                        <div class="alert alert-warning">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Password Expired:</strong> Your password has expired and must be changed.
                        </div>
                    @elseif($isExpiringSoon)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Password Expiring Soon:</strong> Your password will expire in {{ $daysUntilExpiration }} days.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}" id="changePasswordForm">
                        @csrf

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control @error('current_password') is-invalid @enderror" 
                                       id="current_password" 
                                       name="current_password" 
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password_icon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <small id="passwordHelp" class="text-muted">
                                    Password must meet the following requirements:
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation')">
                                    <i class="fas fa-eye" id="password_confirmation_icon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Password Requirements -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">Password Requirements</h6>
                                <div id="passwordRequirements">
                                    <div class="requirement" data-requirement="length">
                                        <i class="fas fa-times text-danger me-2"></i>
                                        <span>At least 8 characters long</span>
                                    </div>
                                    <div class="requirement" data-requirement="uppercase">
                                        <i class="fas fa-times text-danger me-2"></i>
                                        <span>At least 1 uppercase letter</span>
                                    </div>
                                    <div class="requirement" data-requirement="lowercase">
                                        <i class="fas fa-times text-danger me-2"></i>
                                        <span>At least 1 lowercase letter</span>
                                    </div>
                                    <div class="requirement" data-requirement="numbers">
                                        <i class="fas fa-times text-danger me-2"></i>
                                        <span>At least 1 number</span>
                                    </div>
                                    <div class="requirement" data-requirement="special">
                                        <i class="fas fa-times text-danger me-2"></i>
                                        <span>At least 1 special character (!@#$%^&*)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            @if(!$passwordResetRequired)
                                <a href="{{ route('dashboard') }}" class="btn btn-secondary me-md-2">Cancel</a>
                            @endif
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Real-time password validation
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        numbers: /\d/.test(password),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
    };
    
    Object.keys(requirements).forEach(req => {
        const element = document.querySelector(`[data-requirement="${req}"]`);
        const icon = element.querySelector('i');
        
        if (requirements[req]) {
            icon.classList.remove('fa-times', 'text-danger');
            icon.classList.add('fa-check', 'text-success');
        } else {
            icon.classList.remove('fa-check', 'text-success');
            icon.classList.add('fa-times', 'text-danger');
        }
    });
});

// Load password policy on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('/api/password-policy')
        .then(response => response.json())
        .then(data => {
            // Update requirements based on actual policy
            console.log('Password Policy:', data);
        })
        .catch(error => {
            console.error('Error loading password policy:', error);
        });
});
</script>
@endsection