@extends('layouts.app')

@section('title', 'Security Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('inventory.settings.index') }}">Settings</a></li>
                            <li class="breadcrumb-item active">Security Settings</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">Security Settings</h1>
                    <p class="text-muted">Configure security policies, access controls, and audit settings</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary me-2" onclick="exportSecurityConfig()">
                        <i class="fas fa-download"></i> Export Config
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveAllSecuritySettings()">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Alert -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="fas fa-shield-alt me-2"></i>
                <div>
                    <strong>Security Notice:</strong> Changes to security settings will affect all users. Some changes may require users to re-authenticate.
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="securitySettingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                        <i class="fas fa-key"></i> Password Policy
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="access-tab" data-bs-toggle="tab" data-bs-target="#access" type="button" role="tab">
                        <i class="fas fa-user-shield"></i> Access Control
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="session-tab" data-bs-toggle="tab" data-bs-target="#session" type="button" role="tab">
                        <i class="fas fa-clock"></i> Session Management
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="audit-tab" data-bs-toggle="tab" data-bs-target="#audit" type="button" role="tab">
                        <i class="fas fa-clipboard-list"></i> Audit & Logging
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="encryption-tab" data-bs-toggle="tab" data-bs-target="#encryption" type="button" role="tab">
                        <i class="fas fa-lock"></i> Encryption
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="monitoring-tab" data-bs-toggle="tab" data-bs-target="#monitoring" type="button" role="tab">
                        <i class="fas fa-eye"></i> Security Monitoring
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="securitySettingsTabContent">
        <!-- Password Policy Tab -->
        <div class="tab-pane fade show active" id="password" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-key text-primary"></i> Password Requirements
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="passwordPolicyForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="minPasswordLength" class="form-label">Minimum Password Length</label>
                                            <select class="form-select" id="minPasswordLength">
                                                <option value="6">6 characters</option>
                                                <option value="8" selected>8 characters</option>
                                                <option value="10">10 characters</option>
                                                <option value="12">12 characters</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maxPasswordLength" class="form-label">Maximum Password Length</label>
                                            <select class="form-select" id="maxPasswordLength">
                                                <option value="50">50 characters</option>
                                                <option value="100" selected>100 characters</option>
                                                <option value="128">128 characters</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="passwordExpiry" class="form-label">Password Expiry (days)</label>
                                            <select class="form-select" id="passwordExpiry">
                                                <option value="0">Never expires</option>
                                                <option value="30">30 days</option>
                                                <option value="60">60 days</option>
                                                <option value="90" selected>90 days</option>
                                                <option value="180">180 days</option>
                                                <option value="365">365 days</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="passwordHistory" class="form-label">Password History</label>
                                            <select class="form-select" id="passwordHistory">
                                                <option value="0">No restriction</option>
                                                <option value="3">Last 3 passwords</option>
                                                <option value="5" selected>Last 5 passwords</option>
                                                <option value="10">Last 10 passwords</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password Complexity Requirements</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireUppercase" checked>
                                        <label class="form-check-label" for="requireUppercase">
                                            Require uppercase letters (A-Z)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireLowercase" checked>
                                        <label class="form-check-label" for="requireLowercase">
                                            Require lowercase letters (a-z)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireNumbers" checked>
                                        <label class="form-check-label" for="requireNumbers">
                                            Require numbers (0-9)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireSpecialChars" checked>
                                        <label class="form-check-label" for="requireSpecialChars">
                                            Require special characters (!@#$%^&*)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="preventCommonPasswords" checked>
                                        <label class="form-check-label" for="preventCommonPasswords">
                                            Prevent common passwords
                                        </label>
                                    </div>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="forcePasswordChangeOnFirstLogin" checked>
                                    <label class="form-check-label" for="forcePasswordChangeOnFirstLogin">
                                        Force password change on first login
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Two-Factor Authentication -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-mobile-alt text-success"></i> Two-Factor Authentication
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="twoFactorForm">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableTwoFactor">
                                    <label class="form-check-label" for="enableTwoFactor">
                                        <strong>Enable Two-Factor Authentication</strong>
                                    </label>
                                </div>
                                <div id="twoFactorOptions" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Available Methods</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="twoFactorEmail" checked>
                                            <label class="form-check-label" for="twoFactorEmail">
                                                Email verification codes
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="twoFactorSMS">
                                            <label class="form-check-label" for="twoFactorSMS">
                                                SMS verification codes
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="twoFactorApp" checked>
                                            <label class="form-check-label" for="twoFactorApp">
                                                Authenticator app (Google Authenticator, Authy)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="twoFactorCodeExpiry" class="form-label">Code Expiry (minutes)</label>
                                                <select class="form-select" id="twoFactorCodeExpiry">
                                                    <option value="5" selected>5 minutes</option>
                                                    <option value="10">10 minutes</option>
                                                    <option value="15">15 minutes</option>
                                                    <option value="30">30 minutes</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="twoFactorBackupCodes" class="form-label">Backup Codes</label>
                                                <select class="form-select" id="twoFactorBackupCodes">
                                                    <option value="5">5 codes</option>
                                                    <option value="10" selected>10 codes</option>
                                                    <option value="15">15 codes</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="twoFactorRequired">
                                        <label class="form-check-label" for="twoFactorRequired">
                                            Require 2FA for all users
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Password Strength Indicator -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Password Policy Preview</h6>
                        </div>
                        <div class="card-body">
                            <div id="passwordPreview">
                                <div class="mb-2">
                                    <small class="text-muted">Example valid password:</small>
                                    <div class="font-monospace bg-light p-2 rounded">
                                        <span id="examplePassword">MySecure123!</span>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Requirements:</small>
                                    <ul class="list-unstyled small" id="requirementsList">
                                        <li><i class="fas fa-check text-success"></i> At least 8 characters</li>
                                        <li><i class="fas fa-check text-success"></i> Uppercase letter</li>
                                        <li><i class="fas fa-check text-success"></i> Lowercase letter</li>
                                        <li><i class="fas fa-check text-success"></i> Number</li>
                                        <li><i class="fas fa-check text-success"></i> Special character</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Stats -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Security Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div id="securityStats" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Access Control Tab -->
        <div class="tab-pane fade" id="access" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-shield text-info"></i> User Access Control
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="accessControlForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="defaultUserRole" class="form-label">Default User Role</label>
                                            <select class="form-select" id="defaultUserRole">
                                                <option value="viewer" selected>Viewer</option>
                                                <option value="employee">Employee</option>
                                                <option value="manager">Manager</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maxConcurrentSessions" class="form-label">Max Concurrent Sessions</label>
                                            <select class="form-select" id="maxConcurrentSessions">
                                                <option value="1">1 session</option>
                                                <option value="2">2 sessions</option>
                                                <option value="3" selected>3 sessions</option>
                                                <option value="5">5 sessions</option>
                                                <option value="0">Unlimited</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Registration & Access</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allowSelfRegistration">
                                        <label class="form-check-label" for="allowSelfRegistration">
                                            Allow self-registration
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireEmailVerification" checked>
                                        <label class="form-check-label" for="requireEmailVerification">
                                            Require email verification
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allowGuestAccess">
                                        <label class="form-check-label" for="allowGuestAccess">
                                            Allow guest access (read-only)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requireAdminApproval" checked>
                                        <label class="form-check-label" for="requireAdminApproval">
                                            Require admin approval for new accounts
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- IP Restrictions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-globe text-warning"></i> IP Address Restrictions
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="ipRestrictionsForm">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableIpRestrictions">
                                    <label class="form-check-label" for="enableIpRestrictions">
                                        <strong>Enable IP Address Restrictions</strong>
                                    </label>
                                </div>
                                <div id="ipRestrictionsOptions" style="display: none;">
                                    <div class="mb-3">
                                        <label for="allowedIpRanges" class="form-label">Allowed IP Ranges</label>
                                        <textarea class="form-control" id="allowedIpRanges" rows="4" placeholder="192.168.1.0/24&#10;10.0.0.0/8&#10;172.16.0.0/12"></textarea>
                                        <div class="form-text">Enter one IP address or CIDR range per line</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="blockedIpRanges" class="form-label">Blocked IP Ranges</label>
                                        <textarea class="form-control" id="blockedIpRanges" rows="3" placeholder="192.168.100.0/24"></textarea>
                                        <div class="form-text">These IPs will be blocked even if in allowed ranges</div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="logIpViolations" checked>
                                        <label class="form-check-label" for="logIpViolations">
                                            Log IP restriction violations
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Role-Based Permissions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-users-cog text-success"></i> Role-Based Permissions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="rolePermissions" class="table-responsive">
                                <div class="text-center">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading permissions...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Access Control Stats -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Access Control Overview</h6>
                        </div>
                        <div class="card-body">
                            <div id="accessStats" class="text-center">
                                <div class="spinner-border text-info" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Management Tab -->
        <div class="tab-pane fade" id="session" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock text-primary"></i> Session Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="sessionConfigForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="sessionTimeout" class="form-label">Session Timeout (minutes)</label>
                                            <select class="form-select" id="sessionTimeout">
                                                <option value="15">15 minutes</option>
                                                <option value="30" selected>30 minutes</option>
                                                <option value="60">1 hour</option>
                                                <option value="120">2 hours</option>
                                                <option value="480">8 hours</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="rememberMeDuration" class="form-label">Remember Me Duration (days)</label>
                                            <select class="form-select" id="rememberMeDuration">
                                                <option value="7">7 days</option>
                                                <option value="14">14 days</option>
                                                <option value="30" selected>30 days</option>
                                                <option value="90">90 days</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maxLoginAttempts" class="form-label">Max Login Attempts</label>
                                            <select class="form-select" id="maxLoginAttempts">
                                                <option value="3">3 attempts</option>
                                                <option value="5" selected>5 attempts</option>
                                                <option value="10">10 attempts</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="lockoutDuration" class="form-label">Lockout Duration (minutes)</label>
                                            <select class="form-select" id="lockoutDuration">
                                                <option value="5">5 minutes</option>
                                                <option value="15" selected>15 minutes</option>
                                                <option value="30">30 minutes</option>
                                                <option value="60">1 hour</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableSessionWarning" checked>
                                    <label class="form-check-label" for="enableSessionWarning">
                                        Show session timeout warning
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="logoutOnBrowserClose">
                                    <label class="form-check-label" for="logoutOnBrowserClose">
                                        Logout when browser closes
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="singleSessionPerUser">
                                    <label class="form-check-label" for="singleSessionPerUser">
                                        Allow only one session per user
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Active Sessions -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-users text-info"></i> Active Sessions
                            </h5>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="terminateAllSessions()">
                                <i class="fas fa-sign-out-alt"></i> Terminate All
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="activeSessionsTable" class="table-responsive">
                                <div class="text-center">
                                    <div class="spinner-border text-info" role="status">
                                        <span class="visually-hidden">Loading sessions...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Session Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Session Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div id="sessionStats" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit & Logging Tab -->
        <div class="tab-pane fade" id="audit" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clipboard-list text-warning"></i> Audit Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="auditConfigForm">
                                <div class="mb-3">
                                    <label class="form-label">Events to Log</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="logUserLogin" checked>
                                                <label class="form-check-label" for="logUserLogin">
                                                    User login/logout
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="logFailedLogin" checked>
                                                <label class="form-check-label" for="logFailedLogin">
                                                    Failed login attempts
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="logPasswordChanges" checked>
                                                <label class="form-check-label" for="logPasswordChanges">
                                                    Password changes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="logUserCreation" checked>
                                                <label class="form-check-label" for="logUserCreation">
                                                    User creation/deletion
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="logDataChanges" checked>
                                                <label class="form-check-label" for="logDataChanges">
                                                    Data modifications
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="logPermissionChanges" checked>
                                                <label class="form-check-label" for="logPermissionChanges">
                                                    Permission changes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="logSystemChanges" checked>
                                                <label class="form-check-label" for="logSystemChanges">
                                                    System configuration changes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="logFileAccess">
                                                <label class="form-check-label" for="logFileAccess">
                                                    File access/downloads
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="logRetentionDays" class="form-label">Log Retention (days)</label>
                                            <select class="form-select" id="logRetentionDays">
                                                <option value="30">30 days</option>
                                                <option value="90" selected>90 days</option>
                                                <option value="180">180 days</option>
                                                <option value="365">1 year</option>
                                                <option value="1095">3 years</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="logLevel" class="form-label">Log Level</label>
                                            <select class="form-select" id="logLevel">
                                                <option value="error">Error only</option>
                                                <option value="warning">Warning & Error</option>
                                                <option value="info" selected>Info, Warning & Error</option>
                                                <option value="debug">All (Debug mode)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableRealTimeAlerts" checked>
                                    <label class="form-check-label" for="enableRealTimeAlerts">
                                        Enable real-time security alerts
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="exportAuditLogs">
                                    <label class="form-check-label" for="exportAuditLogs">
                                        Allow audit log export
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Recent Security Events -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history text-info"></i> Recent Security Events
                            </h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshSecurityEvents()">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="securityEventsTable" class="table-responsive">
                                <div class="text-center">
                                    <div class="spinner-border text-info" role="status">
                                        <span class="visually-hidden">Loading events...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Audit Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Audit Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div id="auditStats" class="text-center">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Encryption Tab -->
        <div class="tab-pane fade" id="encryption" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-lock text-danger"></i> Encryption Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> Changing encryption settings may require system restart and can affect performance.
                            </div>
                            <form id="encryptionForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="encryptionAlgorithm" class="form-label">Encryption Algorithm</label>
                                            <select class="form-select" id="encryptionAlgorithm">
                                                <option value="aes-256-gcm" selected>AES-256-GCM</option>
                                                <option value="aes-256-cbc">AES-256-CBC</option>
                                                <option value="aes-128-gcm">AES-128-GCM</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="hashingAlgorithm" class="form-label">Password Hashing</label>
                                            <select class="form-select" id="hashingAlgorithm">
                                                <option value="bcrypt" selected>bcrypt</option>
                                                <option value="argon2">Argon2</option>
                                                <option value="scrypt">scrypt</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Data Encryption</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="encryptDatabase" checked>
                                        <label class="form-check-label" for="encryptDatabase">
                                            Encrypt database at rest
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="encryptFiles" checked>
                                        <label class="form-check-label" for="encryptFiles">
                                            Encrypt uploaded files
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="encryptBackups" checked>
                                        <label class="form-check-label" for="encryptBackups">
                                            Encrypt backup files
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="encryptLogs">
                                        <label class="form-check-label" for="encryptLogs">
                                            Encrypt log files
                                        </label>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="forceHttps" checked>
                                    <label class="form-check-label" for="forceHttps">
                                        Force HTTPS connections
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- SSL/TLS Configuration -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-certificate text-success"></i> SSL/TLS Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="sslConfigForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tlsVersion" class="form-label">Minimum TLS Version</label>
                                            <select class="form-select" id="tlsVersion">
                                                <option value="1.2" selected>TLS 1.2</option>
                                                <option value="1.3">TLS 1.3</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="certificateExpiry" class="form-label">Certificate Status</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="Valid until Dec 2024" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="checkCertificate()">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableHsts" checked>
                                    <label class="form-check-label" for="enableHsts">
                                        Enable HTTP Strict Transport Security (HSTS)
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableOcspStapling">
                                    <label class="form-check-label" for="enableOcspStapling">
                                        Enable OCSP Stapling
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Encryption Status -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Encryption Status</h6>
                        </div>
                        <div class="card-body">
                            <div id="encryptionStatus">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <small>Database encryption: Active</small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <small>File encryption: Active</small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <small>HTTPS: Enforced</small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    <small>Log encryption: Disabled</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Monitoring Tab -->
        <div class="tab-pane fade" id="monitoring" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-eye text-info"></i> Security Monitoring
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="monitoringForm">
                                <div class="mb-3">
                                    <label class="form-label">Threat Detection</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="detectBruteForce" checked>
                                        <label class="form-check-label" for="detectBruteForce">
                                            Brute force attack detection
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="detectSqlInjection" checked>
                                        <label class="form-check-label" for="detectSqlInjection">
                                            SQL injection attempts
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="detectXss" checked>
                                        <label class="form-check-label" for="detectXss">
                                            Cross-site scripting (XSS) attempts
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="detectUnusualActivity" checked>
                                        <label class="form-check-label" for="detectUnusualActivity">
                                            Unusual user activity patterns
                                        </label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="alertThreshold" class="form-label">Alert Threshold</label>
                                            <select class="form-select" id="alertThreshold">
                                                <option value="low">Low sensitivity</option>
                                                <option value="medium" selected>Medium sensitivity</option>
                                                <option value="high">High sensitivity</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="monitoringInterval" class="form-label">Monitoring Interval</label>
                                            <select class="form-select" id="monitoringInterval">
                                                <option value="1">Every minute</option>
                                                <option value="5" selected>Every 5 minutes</option>
                                                <option value="15">Every 15 minutes</option>
                                                <option value="60">Every hour</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableAutoBlock" checked>
                                    <label class="form-check-label" for="enableAutoBlock">
                                        Automatically block suspicious IPs
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="sendSecurityAlerts" checked>
                                    <label class="form-check-label" for="sendSecurityAlerts">
                                        Send email alerts for security events
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Security Dashboard -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt text-success"></i> Security Dashboard
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="securityDashboard" class="row text-center">
                                <div class="col-md-3">
                                    <div class="border rounded p-3 mb-3">
                                        <h4 class="text-success mb-0">0</h4>
                                        <small class="text-muted">Active Threats</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 mb-3">
                                        <h4 class="text-warning mb-0">3</h4>
                                        <small class="text-muted">Blocked IPs</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 mb-3">
                                        <h4 class="text-info mb-0">156</h4>
                                        <small class="text-muted">Security Events</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 mb-3">
                                        <h4 class="text-primary mb-0">99.8%</h4>
                                        <small class="text-muted">Uptime</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Threat Intelligence -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Threat Intelligence</h6>
                        </div>
                        <div class="card-body">
                            <div id="threatIntelligence">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-shield-alt text-success"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <div class="fw-bold small">System Status: Secure</div>
                                        <div class="text-muted small">No active threats detected</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-clock text-info"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <div class="fw-bold small">Last Scan</div>
                                        <div class="text-muted small">2 minutes ago</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-database text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <div class="fw-bold small">Threat Database</div>
                                        <div class="text-muted small">Updated daily</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the page
    loadSecurityData();
    
    // Tab change handlers
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            loadTabData(target);
        });
    });
    
    // Two-factor authentication toggle
    document.getElementById('enableTwoFactor').addEventListener('change', function() {
        const options = document.getElementById('twoFactorOptions');
        options.style.display = this.checked ? 'block' : 'none';
    });
    
    // IP restrictions toggle
    document.getElementById('enableIpRestrictions').addEventListener('change', function() {
        const options = document.getElementById('ipRestrictionsOptions');
        options.style.display = this.checked ? 'block' : 'none';
    });
    
    // Password policy change handlers
    document.querySelectorAll('#passwordPolicyForm input, #passwordPolicyForm select').forEach(element => {
        element.addEventListener('change', updatePasswordPreview);
    });
});

function loadSecurityData() {
    loadSecurityStats();
    loadRolePermissions();
    loadActiveSessions();
    loadSecurityEvents();
    updatePasswordPreview();
}

function loadSecurityStats() {
    setTimeout(() => {
        const statsHtml = `
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h4 class="text-success mb-0">98%</h4>
                        <small class="text-muted">Compliance Score</small>
                    </div>
                </div>
                <div class="col-6">
                    <h4 class="text-primary mb-0">45</h4>
                    <small class="text-muted">Active Users</small>
                </div>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h4 class="text-warning mb-0">3</h4>
                        <small class="text-muted">Failed Logins</small>
                    </div>
                </div>
                <div class="col-6">
                    <h4 class="text-info mb-0">12</h4>
                    <small class="text-muted">2FA Enabled</small>
                </div>
            </div>
        `;
        document.getElementById('securityStats').innerHTML = statsHtml;
    }, 1000);
}

function loadRolePermissions() {
    setTimeout(() => {
        const permissionsHtml = `
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Admin</th>
                        <th>Manager</th>
                        <th>Employee</th>
                        <th>Viewer</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>View Items</td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-check text-success"></i></td>
                    </tr>
                    <tr>
                        <td>Create Items</td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-times text-danger"></i></td>
                    </tr>
                    <tr>
                        <td>Delete Items</td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-times text-danger"></i></td>
                        <td><i class="fas fa-times text-danger"></i></td>
                    </tr>
                    <tr>
                        <td>Manage Users</td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-times text-danger"></i></td>
                        <td><i class="fas fa-times text-danger"></i></td>
                        <td><i class="fas fa-times text-danger"></i></td>
                    </tr>
                    <tr>
                        <td>System Settings</td>
                        <td><i class="fas fa-check text-success"></i></td>
                        <td><i class="fas fa-times text-danger"></i></td>
                        <td><i class="fas fa-times text-danger"></i></td>
                        <td><i class="fas fa-times text-danger"></i></td>
                    </tr>
                </tbody>
            </table>
        `;
        document.getElementById('rolePermissions').innerHTML = permissionsHtml;
    }, 800);
}

function loadActiveSessions() {
    setTimeout(() => {
        const sessionsHtml = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>IP Address</th>
                        <th>Device</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>John Doe</td>
                        <td>192.168.1.100</td>
                        <td><i class="fas fa-desktop"></i> Desktop</td>
                        <td>2 minutes ago</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="terminateSession('session1')">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>Jane Smith</td>
                        <td>192.168.1.101</td>
                        <td><i class="fas fa-mobile-alt"></i> Mobile</td>
                        <td>5 minutes ago</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="terminateSession('session2')">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        `;
        document.getElementById('activeSessionsTable').innerHTML = sessionsHtml;
        
        // Load session stats
        const sessionStatsHtml = `
            <div class="text-center">
                <h4 class="text-primary mb-0">23</h4>
                <small class="text-muted">Active Sessions</small>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h5 class="text-success mb-0">18</h5>
                        <small class="text-muted">Desktop</small>
                    </div>
                </div>
                <div class="col-6">
                    <h5 class="text-info mb-0">5</h5>
                    <small class="text-muted">Mobile</small>
                </div>
            </div>
        `;
        document.getElementById('sessionStats').innerHTML = sessionStatsHtml;
    }, 900);
}

function loadSecurityEvents() {
    setTimeout(() => {
        const eventsHtml = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>User</th>
                        <th>IP</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Login Success</td>
                        <td>john.doe</td>
                        <td>192.168.1.100</td>
                        <td>2 min ago</td>
                        <td><span class="badge bg-success">Success</span></td>
                    </tr>
                    <tr>
                        <td>Failed Login</td>
                        <td>unknown</td>
                        <td>203.0.113.1</td>
                        <td>15 min ago</td>
                        <td><span class="badge bg-danger">Failed</span></td>
                    </tr>
                    <tr>
                        <td>Password Change</td>
                        <td>jane.smith</td>
                        <td>192.168.1.101</td>
                        <td>1 hour ago</td>
                        <td><span class="badge bg-info">Info</span></td>
                    </tr>
                </tbody>
            </table>
        `;
        document.getElementById('securityEventsTable').innerHTML = eventsHtml;
        
        // Load audit stats
        const auditStatsHtml = `
            <div class="text-center">
                <h4 class="text-warning mb-0">1,247</h4>
                <small class="text-muted">Total Events</small>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h5 class="text-success mb-0">1,198</h5>
                        <small class="text-muted">Success</small>
                    </div>
                </div>
                <div class="col-6">
                    <h5 class="text-danger mb-0">49</h5>
                    <small class="text-muted">Failed</small>
                </div>
            </div>
        `;
        document.getElementById('auditStats').innerHTML = auditStatsHtml;
    }, 1100);
}

function loadTabData(target) {
    switch(target) {
        case '#access':
            loadAccessStats();
            break;
        case '#session':
            loadSessionStats();
            break;
        case '#audit':
            loadAuditStats();
            break;
        case '#encryption':
            loadEncryptionStatus();
            break;
        case '#monitoring':
            loadMonitoringData();
            break;
    }
}

function loadAccessStats() {
    setTimeout(() => {
        const accessStatsHtml = `
            <div class="text-center">
                <h4 class="text-info mb-0">45</h4>
                <small class="text-muted">Total Users</small>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-4">
                    <h6 class="text-danger mb-0">2</h6>
                    <small class="text-muted">Admin</small>
                </div>
                <div class="col-4">
                    <h6 class="text-warning mb-0">8</h6>
                    <small class="text-muted">Manager</small>
                </div>
                <div class="col-4">
                    <h6 class="text-success mb-0">35</h6>
                    <small class="text-muted">Employee</small>
                </div>
            </div>
        `;
        document.getElementById('accessStats').innerHTML = accessStatsHtml;
    }, 500);
}

function updatePasswordPreview() {
    const minLength = document.getElementById('minPasswordLength').value;
    const requireUpper = document.getElementById('requireUppercase').checked;
    const requireLower = document.getElementById('requireLowercase').checked;
    const requireNumbers = document.getElementById('requireNumbers').checked;
    const requireSpecial = document.getElementById('requireSpecialChars').checked;
    
    let requirements = [];
    requirements.push(`At least ${minLength} characters`);
    if (requireUpper) requirements.push('Uppercase letter');
    if (requireLower) requirements.push('Lowercase letter');
    if (requireNumbers) requirements.push('Number');
    if (requireSpecial) requirements.push('Special character');
    
    const requirementsList = document.getElementById('requirementsList');
    requirementsList.innerHTML = requirements.map(req => 
        `<li><i class="fas fa-check text-success"></i> ${req}</li>`
    ).join('');
    
    // Generate example password
    let example = 'MySecure';
    if (requireNumbers) example += '123';
    if (requireSpecial) example += '!';
    while (example.length < minLength) {
        example += 'x';
    }
    document.getElementById('examplePassword').textContent = example;
}

function saveAllSecuritySettings() {
    // Show loading state
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-check"></i> Saved Successfully';
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-success');
        
        // Show success message
        showAlert('Security settings have been saved successfully!', 'success');
        
        // Reset button after 2 seconds
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-success');
            btn.disabled = false;
        }, 2000);
    }, 1500);
}

function exportSecurityConfig() {
    // Create configuration object
    const config = {
        passwordPolicy: {
            minLength: document.getElementById('minPasswordLength').value,
            maxLength: document.getElementById('maxPasswordLength').value,
            expiry: document.getElementById('passwordExpiry').value,
            history: document.getElementById('passwordHistory').value,
            requireUppercase: document.getElementById('requireUppercase').checked,
            requireLowercase: document.getElementById('requireLowercase').checked,
            requireNumbers: document.getElementById('requireNumbers').checked,
            requireSpecialChars: document.getElementById('requireSpecialChars').checked
        },
        twoFactor: {
            enabled: document.getElementById('enableTwoFactor').checked,
            methods: {
                email: document.getElementById('twoFactorEmail')?.checked || false,
                sms: document.getElementById('twoFactorSMS')?.checked || false,
                app: document.getElementById('twoFactorApp')?.checked || false
            }
        },
        sessionManagement: {
            timeout: document.getElementById('sessionTimeout').value,
            maxAttempts: document.getElementById('maxLoginAttempts').value,
            lockoutDuration: document.getElementById('lockoutDuration').value
        },
        exportedAt: new Date().toISOString()
    };
    
    // Download as JSON file
    const blob = new Blob([JSON.stringify(config, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `security-config-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showAlert('Security configuration exported successfully!', 'info');
}

function terminateSession(sessionId) {
    if (confirm('Are you sure you want to terminate this session?')) {
        // Simulate API call
        showAlert('Session terminated successfully!', 'warning');
        // Reload active sessions
        loadActiveSessions();
    }
}

function terminateAllSessions() {
    if (confirm('Are you sure you want to terminate ALL active sessions? This will log out all users.')) {
        // Simulate API call
        showAlert('All sessions have been terminated!', 'warning');
        // Reload active sessions
        loadActiveSessions();
    }
}

function refreshSecurityEvents() {
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    setTimeout(() => {
        loadSecurityEvents();
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        showAlert('Security events refreshed!', 'info');
    }, 1000);
}

function checkCertificate() {
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        showAlert('Certificate status verified - Valid until Dec 2024', 'success');
    }, 1500);
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

// Additional helper functions for other tabs
function loadEncryptionStatus() {
    // Already implemented in HTML
}

function loadMonitoringData() {
    // Already implemented in HTML
}

function loadAuditStats() {
    // Already loaded in loadSecurityEvents function
}
</script>

@endsection