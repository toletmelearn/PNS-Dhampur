@extends('layouts.app')

@section('title', 'Notification Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('inventory.settings.index') }}">Settings</a></li>
                            <li class="breadcrumb-item active">Notifications</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">Notification Settings</h1>
                    <p class="text-muted">Configure email, SMS, and system notification preferences</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="testNotifications()">
                        <i class="fas fa-paper-plane"></i> Test Notifications
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="exportNotificationConfig()">
                        <i class="fas fa-download"></i> Export Config
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveAllNotificationSettings()">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs nav-tabs-custom" id="notificationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                        <i class="fas fa-envelope"></i> Email Notifications
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms" type="button" role="tab">
                        <i class="fas fa-sms"></i> SMS Notifications
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="push-tab" data-bs-toggle="tab" data-bs-target="#push" type="button" role="tab">
                        <i class="fas fa-bell"></i> Push Notifications
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                        <i class="fas fa-file-alt"></i> Templates
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="channels-tab" data-bs-toggle="tab" data-bs-target="#channels" type="button" role="tab">
                        <i class="fas fa-broadcast-tower"></i> Channels
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">
                        <i class="fas fa-history"></i> Notification Logs
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="notificationTabContent">
        <!-- Email Notifications Tab -->
        <div class="tab-pane fade show active" id="email" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <!-- SMTP Configuration -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-server text-primary"></i> SMTP Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="smtpConfigForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtpHost" class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" id="smtpHost" value="smtp.gmail.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtpPort" class="form-label">SMTP Port</label>
                                            <select class="form-select" id="smtpPort">
                                                <option value="587" selected>587 (TLS)</option>
                                                <option value="465">465 (SSL)</option>
                                                <option value="25">25 (Plain)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtpUsername" class="form-label">Username</label>
                                            <input type="email" class="form-control" id="smtpUsername" placeholder="your-email@gmail.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtpPassword" class="form-label">Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="smtpPassword" placeholder="App Password">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('smtpPassword')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="smtpEncryption" class="form-label">Encryption</label>
                                            <select class="form-select" id="smtpEncryption">
                                                <option value="tls" selected>TLS</option>
                                                <option value="ssl">SSL</option>
                                                <option value="none">None</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fromEmail" class="form-label">From Email</label>
                                            <input type="email" class="form-control" id="fromEmail" placeholder="noreply@company.com">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="fromName" class="form-label">From Name</label>
                                    <input type="text" class="form-control" id="fromName" value="PNS Dhampur Inventory System">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary" onclick="testSmtpConnection()">
                                        <i class="fas fa-plug"></i> Test Connection
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="sendTestEmail()">
                                        <i class="fas fa-paper-plane"></i> Send Test Email
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Email Notification Types -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list-check text-success"></i> Email Notification Types
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">System Notifications</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailLowStock" checked>
                                        <label class="form-check-label" for="emailLowStock">
                                            Low Stock Alerts
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailMaintenanceDue" checked>
                                        <label class="form-check-label" for="emailMaintenanceDue">
                                            Maintenance Due
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailOverdueReturns" checked>
                                        <label class="form-check-label" for="emailOverdueReturns">
                                            Overdue Returns
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailWarrantyExpiry">
                                        <label class="form-check-label" for="emailWarrantyExpiry">
                                            Warranty Expiry
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailSystemErrors" checked>
                                        <label class="form-check-label" for="emailSystemErrors">
                                            System Errors
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">User Notifications</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailNewUser" checked>
                                        <label class="form-check-label" for="emailNewUser">
                                            New User Registration
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailItemAllocation" checked>
                                        <label class="form-check-label" for="emailItemAllocation">
                                            Item Allocation
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailReturnReminder" checked>
                                        <label class="form-check-label" for="emailReturnReminder">
                                            Return Reminders
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailReportDelivery">
                                        <label class="form-check-label" for="emailReportDelivery">
                                            Automated Report Delivery
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailPasswordReset" checked>
                                        <label class="form-check-label" for="emailPasswordReset">
                                            Password Reset
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Frequency Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock text-warning"></i> Email Frequency Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="emailFrequency" class="form-label">Notification Frequency</label>
                                        <select class="form-select" id="emailFrequency">
                                            <option value="immediate" selected>Immediate</option>
                                            <option value="hourly">Hourly Digest</option>
                                            <option value="daily">Daily Digest</option>
                                            <option value="weekly">Weekly Summary</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="digestTime" class="form-label">Digest Time</label>
                                        <input type="time" class="form-control" id="digestTime" value="09:00">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="maxEmailsPerHour" class="form-label">Max Emails per Hour</label>
                                        <input type="number" class="form-control" id="maxEmailsPerHour" value="10" min="1" max="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="quietHoursStart" class="form-label">Quiet Hours Start</label>
                                        <input type="time" class="form-control" id="quietHoursStart" value="22:00">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Email Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-bar text-info"></i> Email Statistics
                            </h6>
                        </div>
                        <div class="card-body" id="emailStats">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Email Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-history text-secondary"></i> Recent Activity
                            </h6>
                        </div>
                        <div class="card-body" id="recentEmailActivity">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Notifications Tab -->
        <div class="tab-pane fade" id="sms" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <!-- SMS Provider Configuration -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-mobile-alt text-primary"></i> SMS Provider Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="smsConfigForm">
                                <div class="mb-3">
                                    <label for="smsProvider" class="form-label">SMS Provider</label>
                                    <select class="form-select" id="smsProvider" onchange="updateSmsConfig()">
                                        <option value="twilio" selected>Twilio</option>
                                        <option value="nexmo">Vonage (Nexmo)</option>
                                        <option value="aws">AWS SNS</option>
                                        <option value="custom">Custom Provider</option>
                                    </select>
                                </div>
                                
                                <div id="twilioConfig">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="twilioSid" class="form-label">Account SID</label>
                                                <input type="text" class="form-control" id="twilioSid" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="twilioToken" class="form-label">Auth Token</label>
                                                <div class="input-group">
                                                    <input type="password" class="form-control" id="twilioToken" placeholder="Your Auth Token">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('twilioToken')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="twilioFromNumber" class="form-label">From Number</label>
                                        <input type="tel" class="form-control" id="twilioFromNumber" placeholder="+1234567890">
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary" onclick="testSmsConnection()">
                                        <i class="fas fa-plug"></i> Test Connection
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="sendTestSms()">
                                        <i class="fas fa-sms"></i> Send Test SMS
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- SMS Notification Types -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list-check text-success"></i> SMS Notification Types
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Critical Alerts</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="smsCriticalStock" checked>
                                        <label class="form-check-label" for="smsCriticalStock">
                                            Critical Stock Levels
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="smsSystemDown" checked>
                                        <label class="form-check-label" for="smsSystemDown">
                                            System Down Alerts
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="smsSecurityBreach" checked>
                                        <label class="form-check-label" for="smsSecurityBreach">
                                            Security Breach
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="smsUrgentMaintenance">
                                        <label class="form-check-label" for="smsUrgentMaintenance">
                                            Urgent Maintenance
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">User Alerts</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="smsOverdueItems" checked>
                                        <label class="form-check-label" for="smsOverdueItems">
                                            Overdue Item Returns
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="smsPasswordReset" checked>
                                        <label class="form-check-label" for="smsPasswordReset">
                                            Password Reset OTP
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="smsTwoFactor" checked>
                                        <label class="form-check-label" for="smsTwoFactor">
                                            Two-Factor Authentication
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="smsAccountLocked">
                                        <label class="form-check-label" for="smsAccountLocked">
                                            Account Locked
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SMS Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cog text-warning"></i> SMS Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smsMaxLength" class="form-label">Max Message Length</label>
                                        <select class="form-select" id="smsMaxLength">
                                            <option value="160" selected>160 characters (Single SMS)</option>
                                            <option value="320">320 characters (2 SMS)</option>
                                            <option value="480">480 characters (3 SMS)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smsRetryAttempts" class="form-label">Retry Attempts</label>
                                        <input type="number" class="form-control" id="smsRetryAttempts" value="3" min="1" max="5">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smsRateLimit" class="form-label">Rate Limit (per minute)</label>
                                        <input type="number" class="form-control" id="smsRateLimit" value="10" min="1" max="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smsQuietHours" class="form-label">Quiet Hours</label>
                                        <div class="input-group">
                                            <input type="time" class="form-control" id="smsQuietStart" value="22:00">
                                            <span class="input-group-text">to</span>
                                            <input type="time" class="form-control" id="smsQuietEnd" value="08:00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- SMS Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-line text-info"></i> SMS Statistics
                            </h6>
                        </div>
                        <div class="card-body" id="smsStats">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SMS Balance -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-wallet text-success"></i> SMS Balance
                            </h6>
                        </div>
                        <div class="card-body" id="smsBalance">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Push Notifications Tab -->
        <div class="tab-pane fade" id="push" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Push Notification Configuration -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bell text-primary"></i> Push Notification Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="pushConfigForm">
                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="enablePushNotifications" checked>
                                        <label class="form-check-label" for="enablePushNotifications">
                                            <strong>Enable Push Notifications</strong>
                                        </label>
                                    </div>
                                    <small class="text-muted">Allow the system to send push notifications to users' browsers and devices</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="pushServiceProvider" class="form-label">Service Provider</label>
                                            <select class="form-select" id="pushServiceProvider">
                                                <option value="firebase" selected>Firebase Cloud Messaging</option>
                                                <option value="onesignal">OneSignal</option>
                                                <option value="pusher">Pusher</option>
                                                <option value="custom">Custom Service</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="pushApiKey" class="form-label">API Key</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="pushApiKey" placeholder="Your API Key">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('pushApiKey')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="pushAppId" class="form-label">Application ID</label>
                                    <input type="text" class="form-control" id="pushAppId" placeholder="Your Application ID">
                                </div>

                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">Notification Preferences</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="pushBrowserNotifications" checked>
                                                <label class="form-check-label" for="pushBrowserNotifications">
                                                    Browser Notifications
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="pushSoundAlerts" checked>
                                                <label class="form-check-label" for="pushSoundAlerts">
                                                    Sound Alerts
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="pushBadgeUpdates" checked>
                                                <label class="form-check-label" for="pushBadgeUpdates">
                                                    Badge Updates
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="pushDesktopNotifications" checked>
                                                <label class="form-check-label" for="pushDesktopNotifications">
                                                    Desktop Notifications
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="pushMobileNotifications">
                                                <label class="form-check-label" for="pushMobileNotifications">
                                                    Mobile App Notifications
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="pushInAppNotifications" checked>
                                                <label class="form-check-label" for="pushInAppNotifications">
                                                    In-App Notifications
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary" onclick="testPushService()">
                                        <i class="fas fa-plug"></i> Test Service
                                    </button>
                                    <button type="button" class="btn btn-outline-success" onclick="sendTestPush()">
                                        <i class="fas fa-bell"></i> Send Test Push
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Push Notification Types -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list-check text-success"></i> Push Notification Types
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Real-time Alerts</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pushNewAllocation" checked>
                                        <label class="form-check-label" for="pushNewAllocation">
                                            New Item Allocation
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pushReturnReminder" checked>
                                        <label class="form-check-label" for="pushReturnReminder">
                                            Return Reminders
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pushMaintenanceAlert" checked>
                                        <label class="form-check-label" for="pushMaintenanceAlert">
                                            Maintenance Alerts
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pushLowStockAlert" checked>
                                        <label class="form-check-label" for="pushLowStockAlert">
                                            Low Stock Alerts
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">System Updates</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pushSystemUpdate">
                                        <label class="form-check-label" for="pushSystemUpdate">
                                            System Updates
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pushNewFeature">
                                        <label class="form-check-label" for="pushNewFeature">
                                            New Features
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pushScheduledMaintenance">
                                        <label class="form-check-label" for="pushScheduledMaintenance">
                                            Scheduled Maintenance
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pushReportReady">
                                        <label class="form-check-label" for="pushReportReady">
                                            Report Ready
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Push Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-pie text-info"></i> Push Statistics
                            </h6>
                        </div>
                        <div class="card-body" id="pushStats">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Browser Support -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-browser text-secondary"></i> Browser Support
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Chrome</span>
                                <span class="badge bg-success">Supported</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Firefox</span>
                                <span class="badge bg-success">Supported</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Safari</span>
                                <span class="badge bg-warning">Limited</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Edge</span>
                                <span class="badge bg-success">Supported</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Opera</span>
                                <span class="badge bg-success">Supported</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates Tab -->
        <div class="tab-pane fade" id="templates" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Template Management -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt text-primary"></i> Notification Templates
                            </h5>
                            <button type="button" class="btn btn-primary btn-sm" onclick="createNewTemplate()">
                                <i class="fas fa-plus"></i> New Template
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Template Name</th>
                                            <th>Type</th>
                                            <th>Subject/Title</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="templatesTable">
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Template Editor -->
                    <div class="card" id="templateEditor" style="display: none;">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-edit text-success"></i> Template Editor
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="templateForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="templateName" class="form-label">Template Name</label>
                                            <input type="text" class="form-control" id="templateName" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="templateType" class="form-label">Template Type</label>
                                            <select class="form-select" id="templateType" required>
                                                <option value="">Select Type</option>
                                                <option value="email">Email</option>
                                                <option value="sms">SMS</option>
                                                <option value="push">Push Notification</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="templateSubject" class="form-label">Subject/Title</label>
                                    <input type="text" class="form-control" id="templateSubject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="templateContent" class="form-label">Content</label>
                                    <textarea class="form-control" id="templateContent" rows="8" required></textarea>
                                    <small class="text-muted">
                                        Available variables: {{user_name}}, {{item_name}}, {{due_date}}, {{company_name}}
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-success" onclick="saveTemplate()">
                                        <i class="fas fa-save"></i> Save Template
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="previewTemplate()">
                                        <i class="fas fa-eye"></i> Preview
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="cancelTemplateEdit()">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Template Variables -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-code text-info"></i> Available Variables
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-muted">User Variables</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-light text-dark">{{user_name}}</span>
                                    <span class="badge bg-light text-dark">{{user_email}}</span>
                                    <span class="badge bg-light text-dark">{{user_role}}</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-muted">Item Variables</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-light text-dark">{{item_name}}</span>
                                    <span class="badge bg-light text-dark">{{item_id}}</span>
                                    <span class="badge bg-light text-dark">{{category}}</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <h6 class="text-muted">Date Variables</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-light text-dark">{{due_date}}</span>
                                    <span class="badge bg-light text-dark">{{current_date}}</span>
                                    <span class="badge bg-light text-dark">{{allocation_date}}</span>
                                </div>
                            </div>
                            <div>
                                <h6 class="text-muted">System Variables</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-light text-dark">{{company_name}}</span>
                                    <span class="badge bg-light text-dark">{{system_url}}</span>
                                    <span class="badge bg-light text-dark">{{support_email}}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Preview -->
                    <div class="card" id="templatePreview" style="display: none;">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-eye text-secondary"></i> Template Preview
                            </h6>
                        </div>
                        <div class="card-body" id="previewContent">
                            <!-- Preview content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Channels Tab -->
        <div class="tab-pane fade" id="channels" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Notification Channels -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-broadcast-tower text-primary"></i> Notification Channels
                            </h5>
                            <button type="button" class="btn btn-primary btn-sm" onclick="addNotificationChannel()">
                                <i class="fas fa-plus"></i> Add Channel
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Channel Name</th>
                                            <th>Type</th>
                                            <th>Recipients</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="channelsTable">
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Channel Rules -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-rules text-warning"></i> Channel Rules & Routing
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Configure rules to automatically route notifications to appropriate channels based on priority, type, or recipient.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Priority-based Routing</h6>
                                    <div class="mb-3">
                                        <label class="form-label">High Priority Notifications</label>
                                        <select class="form-select" multiple>
                                            <option value="email" selected>Email</option>
                                            <option value="sms" selected>SMS</option>
                                            <option value="push" selected>Push</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Medium Priority Notifications</label>
                                        <select class="form-select" multiple>
                                            <option value="email" selected>Email</option>
                                            <option value="push" selected>Push</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Low Priority Notifications</label>
                                        <select class="form-select" multiple>
                                            <option value="email" selected>Email</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Time-based Routing</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Business Hours (9 AM - 6 PM)</label>
                                        <select class="form-select" multiple>
                                            <option value="email" selected>Email</option>
                                            <option value="push" selected>Push</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">After Hours</label>
                                        <select class="form-select" multiple>
                                            <option value="email" selected>Email</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Emergency (24/7)</label>
                                        <select class="form-select" multiple>
                                            <option value="sms" selected>SMS</option>
                                            <option value="push" selected>Push</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Channel Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-bar text-info"></i> Channel Performance
                            </h6>
                        </div>
                        <div class="card-body" id="channelStats">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Status -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-truck text-success"></i> Delivery Status
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Email Delivery Rate</span>
                                <span class="text-success">98.5%</span>
                            </div>
                            <div class="progress mb-3" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 98.5%"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>SMS Delivery Rate</span>
                                <span class="text-success">99.2%</span>
                            </div>
                            <div class="progress mb-3" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 99.2%"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Push Delivery Rate</span>
                                <span class="text-warning">85.7%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: 85.7%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Logs Tab -->
        <div class="tab-pane fade" id="logs" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <!-- Log Filters -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-filter text-primary"></i> Log Filters
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="logDateRange" class="form-label">Date Range</label>
                                        <select class="form-select" id="logDateRange">
                                            <option value="today">Today</option>
                                            <option value="yesterday">Yesterday</option>
                                            <option value="week" selected>Last 7 days</option>
                                            <option value="month">Last 30 days</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="logType" class="form-label">Notification Type</label>
                                        <select class="form-select" id="logType">
                                            <option value="all" selected>All Types</option>
                                            <option value="email">Email</option>
                                            <option value="sms">SMS</option>
                                            <option value="push">Push</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="logStatus" class="form-label">Status</label>
                                        <select class="form-select" id="logStatus">
                                            <option value="all" selected>All Status</option>
                                            <option value="sent">Sent</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="failed">Failed</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="logSearch" class="form-label">Search</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="logSearch" placeholder="Search logs...">
                                            <button class="btn btn-outline-secondary" type="button" onclick="searchLogs()">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" onclick="applyLogFilters()">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="clearLogFilters()">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="exportLogs()">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Logs -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history text-secondary"></i> Notification Logs
                            </h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshLogs()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Type</th>
                                            <th>Recipient</th>
                                            <th>Subject/Message</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="logsTable">
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <nav aria-label="Log pagination">
                                <ul class="pagination justify-content-center" id="logsPagination">
                                    <!-- Pagination will be loaded here -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Notification Modal -->
<div class="modal fade" id="testNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="testNotificationForm">
                    <div class="mb-3">
                        <label for="testNotificationType" class="form-label">Notification Type</label>
                        <select class="form-select" id="testNotificationType" required>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="push">Push Notification</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="testRecipient" class="form-label">Recipient</label>
                        <input type="text" class="form-control" id="testRecipient" placeholder="Email or Phone Number" required>
                    </div>
                    <div class="mb-3">
                        <label for="testMessage" class="form-label">Test Message</label>
                        <textarea class="form-control" id="testMessage" rows="3" placeholder="Enter test message...">This is a test notification from PNS Dhampur Inventory System.</textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestNotification()">Send Test</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
.nav-tabs-custom {
    border-bottom: 2px solid #e9ecef;
}

.nav-tabs-custom .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: #6c757d;
    font-weight: 500;
    padding: 1rem 1.5rem;
}

.nav-tabs-custom .nav-link:hover {
    border-bottom-color: #dee2e6;
    color: #495057;
}

.nav-tabs-custom .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.card-title {
    font-weight: 600;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.badge {
    font-size: 0.75em;
}

.progress {
    background-color: #e9ecef;
}

.spinner-border {
    width: 1.5rem;
    height: 1.5rem;
}

.table th {
    font-weight: 600;
    color: #495057;
    border-top: none;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.alert {
    border: none;
    border-radius: 0.5rem;
}

.input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.text-muted {
    color: #6c757d !important;
}

.breadcrumb {
    background: none;
    padding: 0;
    margin: 0;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: ">";
    color: #6c757d;
}

.breadcrumb-item a {
    color: #0d6efd;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #6c757d;
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize the page
    initializeNotificationSettings();
    loadEmailStats();
    loadSmsStats();
    loadPushStats();
    loadChannelStats();
    loadNotificationTemplates();
    loadNotificationChannels();
    loadNotificationLogs();
});

// Initialize notification settings
function initializeNotificationSettings() {
    // Load saved settings from localStorage or server
    loadSavedSettings();
    
    // Set up event listeners
    setupEventListeners();
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Setup event listeners
function setupEventListeners() {
    // Tab change events
    $('#notificationTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('data-bs-target');
        handleTabChange(target);
    });
    
    // Form change events
    $('input, select, textarea').on('change', function() {
        markAsUnsaved();
    });
    
    // Auto-save functionality
    setInterval(autoSave, 30000); // Auto-save every 30 seconds
}

// Handle tab changes
function handleTabChange(target) {
    switch(target) {
        case '#email':
            loadEmailStats();
            break;
        case '#sms':
            loadSmsStats();
            break;
        case '#push':
            loadPushStats();
            break;
        case '#templates':
            loadNotificationTemplates();
            break;
        case '#channels':
            loadNotificationChannels();
            break;
        case '#logs':
            loadNotificationLogs();
            break;
    }
}

// Load saved settings
function loadSavedSettings() {
    // Load SMTP settings
    const smtpSettings = JSON.parse(localStorage.getItem('smtpSettings') || '{}');
    if (smtpSettings.host) $('#smtpHost').val(smtpSettings.host);
    if (smtpSettings.port) $('#smtpPort').val(smtpSettings.port);
    if (smtpSettings.username) $('#smtpUsername').val(smtpSettings.username);
    if (smtpSettings.encryption) $('#smtpEncryption').val(smtpSettings.encryption);
    if (smtpSettings.fromEmail) $('#fromEmail').val(smtpSettings.fromEmail);
    if (smtpSettings.fromName) $('#fromName').val(smtpSettings.fromName);
    
    // Load notification preferences
    const emailPrefs = JSON.parse(localStorage.getItem('emailNotificationPrefs') || '{}');
    Object.keys(emailPrefs).forEach(key => {
        if (emailPrefs[key]) {
            $(`#${key}`).prop('checked', true);
        }
    });
    
    // Load SMS settings
    const smsSettings = JSON.parse(localStorage.getItem('smsSettings') || '{}');
    if (smsSettings.provider) $('#smsProvider').val(smsSettings.provider);
    if (smsSettings.twilioSid) $('#twilioSid').val(smsSettings.twilioSid);
    if (smsSettings.twilioFromNumber) $('#twilioFromNumber').val(smsSettings.twilioFromNumber);
    
    // Load push settings
    const pushSettings = JSON.parse(localStorage.getItem('pushSettings') || '{}');
    if (pushSettings.provider) $('#pushServiceProvider').val(pushSettings.provider);
    if (pushSettings.appId) $('#pushAppId').val(pushSettings.appId);
    $('#enablePushNotifications').prop('checked', pushSettings.enabled !== false);
}

// Mark form as unsaved
function markAsUnsaved() {
    if (!window.hasUnsavedChanges) {
        window.hasUnsavedChanges = true;
        $('button[onclick="saveAllNotificationSettings()"]').addClass('btn-warning').removeClass('btn-success');
    }
}

// Auto-save functionality
function autoSave() {
    if (window.hasUnsavedChanges) {
        saveAllNotificationSettings(true);
    }
}

// Save all notification settings
function saveAllNotificationSettings(isAutoSave = false) {
    const settings = {
        smtp: {
            host: $('#smtpHost').val(),
            port: $('#smtpPort').val(),
            username: $('#smtpUsername').val(),
            password: $('#smtpPassword').val(),
            encryption: $('#smtpEncryption').val(),
            fromEmail: $('#fromEmail').val(),
            fromName: $('#fromName').val()
        },
        emailNotifications: {
            lowStock: $('#emailLowStock').is(':checked'),
            maintenanceDue: $('#emailMaintenanceDue').is(':checked'),
            overdueReturns: $('#emailOverdueReturns').is(':checked'),
            warrantyExpiry: $('#emailWarrantyExpiry').is(':checked'),
            systemErrors: $('#emailSystemErrors').is(':checked'),
            newUser: $('#emailNewUser').is(':checked'),
            itemAllocation: $('#emailItemAllocation').is(':checked'),
            returnReminder: $('#emailReturnReminder').is(':checked'),
            reportDelivery: $('#emailReportDelivery').is(':checked'),
            passwordReset: $('#emailPasswordReset').is(':checked')
        },
        emailSettings: {
            frequency: $('#emailFrequency').val(),
            digestTime: $('#digestTime').val(),
            maxEmailsPerHour: $('#maxEmailsPerHour').val(),
            quietHoursStart: $('#quietHoursStart').val()
        },
        sms: {
            provider: $('#smsProvider').val(),
            twilioSid: $('#twilioSid').val(),
            twilioToken: $('#twilioToken').val(),
            twilioFromNumber: $('#twilioFromNumber').val(),
            maxLength: $('#smsMaxLength').val(),
            retryAttempts: $('#smsRetryAttempts').val(),
            rateLimit: $('#smsRateLimit').val(),
            quietStart: $('#smsQuietStart').val(),
            quietEnd: $('#smsQuietEnd').val()
        },
        smsNotifications: {
            criticalStock: $('#smsCriticalStock').is(':checked'),
            systemDown: $('#smsSystemDown').is(':checked'),
            securityBreach: $('#smsSecurityBreach').is(':checked'),
            urgentMaintenance: $('#smsUrgentMaintenance').is(':checked'),
            overdueItems: $('#smsOverdueItems').is(':checked'),
            passwordReset: $('#smsPasswordReset').is(':checked'),
            twoFactor: $('#smsTwoFactor').is(':checked'),
            accountLocked: $('#smsAccountLocked').is(':checked')
        },
        push: {
            enabled: $('#enablePushNotifications').is(':checked'),
            provider: $('#pushServiceProvider').val(),
            apiKey: $('#pushApiKey').val(),
            appId: $('#pushAppId').val(),
            browserNotifications: $('#pushBrowserNotifications').is(':checked'),
            soundAlerts: $('#pushSoundAlerts').is(':checked'),
            badgeUpdates: $('#pushBadgeUpdates').is(':checked'),
            desktopNotifications: $('#pushDesktopNotifications').is(':checked'),
            mobileNotifications: $('#pushMobileNotifications').is(':checked'),
            inAppNotifications: $('#pushInAppNotifications').is(':checked')
        },
        pushNotifications: {
            newAllocation: $('#pushNewAllocation').is(':checked'),
            returnReminder: $('#pushReturnReminder').is(':checked'),
            maintenanceAlert: $('#pushMaintenanceAlert').is(':checked'),
            lowStockAlert: $('#pushLowStockAlert').is(':checked'),
            systemUpdate: $('#pushSystemUpdate').is(':checked'),
            newFeature: $('#pushNewFeature').is(':checked'),
            scheduledMaintenance: $('#pushScheduledMaintenance').is(':checked'),
            reportReady: $('#pushReportReady').is(':checked')
        }
    };
    
    // Save to localStorage
    localStorage.setItem('smtpSettings', JSON.stringify(settings.smtp));
    localStorage.setItem('emailNotificationPrefs', JSON.stringify(settings.emailNotifications));
    localStorage.setItem('emailSettings', JSON.stringify(settings.emailSettings));
    localStorage.setItem('smsSettings', JSON.stringify(settings.sms));
    localStorage.setItem('smsNotificationPrefs', JSON.stringify(settings.smsNotifications));
    localStorage.setItem('pushSettings', JSON.stringify(settings.push));
    localStorage.setItem('pushNotificationPrefs', JSON.stringify(settings.pushNotifications));
    
    // Send to server
    $.ajax({
        url: '{{ route("inventory.settings.notifications.save") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            settings: settings
        },
        success: function(response) {
            if (!isAutoSave) {
                showNotification('Settings saved successfully!', 'success');
                $('button[onclick="saveAllNotificationSettings()"]').removeClass('btn-warning').addClass('btn-success');
                window.hasUnsavedChanges = false;
            }
        },
        error: function(xhr) {
            if (!isAutoSave) {
                showNotification('Error saving settings: ' + xhr.responseJSON.message, 'error');
            }
        }
    });
}

// Test SMTP connection
function testSmtpConnection() {
    const settings = {
        host: $('#smtpHost').val(),
        port: $('#smtpPort').val(),
        username: $('#smtpUsername').val(),
        password: $('#smtpPassword').val(),
        encryption: $('#smtpEncryption').val()
    };
    
    if (!settings.host || !settings.username || !settings.password) {
        showNotification('Please fill in all SMTP settings first.', 'warning');
        return;
    }
    
    const btn = $('button[onclick="testSmtpConnection()"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
    
    $.ajax({
        url: '{{ route("inventory.settings.notifications.test-smtp") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            settings: settings
        },
        success: function(response) {
            showNotification('SMTP connection successful!', 'success');
        },
        error: function(xhr) {
            showNotification('SMTP connection failed: ' + xhr.responseJSON.message, 'error');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Test Connection');
        }
    });
}

// Send test email
function sendTestEmail() {
    const testEmail = prompt('Enter email address to send test email:');
    if (!testEmail) return;
    
    const btn = $('button[onclick="sendTestEmail()"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
    
    $.ajax({
        url: '{{ route("inventory.settings.notifications.test-email") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            email: testEmail
        },
        success: function(response) {
            showNotification('Test email sent successfully!', 'success');
        },
        error: function(xhr) {
            showNotification('Failed to send test email: ' + xhr.responseJSON.message, 'error');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Test Email');
        }
    });
}

// Update SMS configuration based on provider
function updateSmsConfig() {
    const provider = $('#smsProvider').val();
    
    // Hide all config sections
    $('#twilioConfig, #nexmoConfig, #awsConfig, #customConfig').hide();
    
    // Show relevant config section
    switch(provider) {
        case 'twilio':
            $('#twilioConfig').show();
            break;
        case 'nexmo':
            $('#nexmoConfig').show();
            break;
        case 'aws':
            $('#awsConfig').show();
            break;
        case 'custom':
            $('#customConfig').show();
            break;
    }
}

// Test SMS connection
function testSmsConnection() {
    const provider = $('#smsProvider').val();
    let settings = { provider: provider };
    
    if (provider === 'twilio') {
        settings.sid = $('#twilioSid').val();
        settings.token = $('#twilioToken').val();
        settings.fromNumber = $('#twilioFromNumber').val();
        
        if (!settings.sid || !settings.token || !settings.fromNumber) {
            showNotification('Please fill in all Twilio settings first.', 'warning');
            return;
        }
    }
    
    const btn = $('button[onclick="testSmsConnection()"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
    
    $.ajax({
        url: '{{ route("inventory.settings.notifications.test-sms-connection") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            settings: settings
        },
        success: function(response) {
            showNotification('SMS connection successful!', 'success');
        },
        error: function(xhr) {
            showNotification('SMS connection failed: ' + xhr.responseJSON.message, 'error');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Test Connection');
        }
    });
}

// Send test SMS
function sendTestSms() {
    const testPhone = prompt('Enter phone number to send test SMS (with country code):');
    if (!testPhone) return;
    
    const btn = $('button[onclick="sendTestSms()"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
    
    $.ajax({
        url: '{{ route("inventory.settings.notifications.test-sms") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            phone: testPhone
        },
        success: function(response) {
            showNotification('Test SMS sent successfully!', 'success');
        },
        error: function(xhr) {
            showNotification('Failed to send test SMS: ' + xhr.responseJSON.message, 'error');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sms"></i> Send Test SMS');
        }
    });
}

// Test push service
function testPushService() {
    const settings = {
        provider: $('#pushServiceProvider').val(),
        apiKey: $('#pushApiKey').val(),
        appId: $('#pushAppId').val()
    };
    
    if (!settings.apiKey || !settings.appId) {
        showNotification('Please fill in API Key and Application ID first.', 'warning');
        return;
    }
    
    const btn = $('button[onclick="testPushService()"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
    
    $.ajax({
        url: '{{ route("inventory.settings.notifications.test-push-connection") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            settings: settings
        },
        success: function(response) {
            showNotification('Push service connection successful!', 'success');
        },
        error: function(xhr) {
            showNotification('Push service connection failed: ' + xhr.responseJSON.message, 'error');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Test Service');
        }
    });
}

// Send test push notification
function sendTestPush() {
    if (!('Notification' in window)) {
        showNotification('This browser does not support push notifications.', 'warning');
        return;
    }
    
    if (Notification.permission === 'denied') {
        showNotification('Push notifications are blocked. Please enable them in your browser settings.', 'warning');
        return;
    }
    
    if (Notification.permission === 'default') {
        Notification.requestPermission().then(function(permission) {
            if (permission === 'granted') {
                sendTestPushNotification();
            }
        });
    } else {
        sendTestPushNotification();
    }
}

// Send test push notification
function sendTestPushNotification() {
    const notification = new Notification('Test Notification', {
        body: 'This is a test push notification from PNS Dhampur Inventory System.',
        icon: '/favicon.ico',
        badge: '/favicon.ico'
    });
    
    notification.onclick = function() {
        window.focus();
        notification.close();
    };
    
    setTimeout(function() {
        notification.close();
    }, 5000);
    
    showNotification('Test push notification sent!', 'success');
}

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        button.classList.remove('fa-eye');
        button.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        button.classList.remove('fa-eye-slash');
        button.classList.add('fa-eye');
    }
}

// Load email statistics
function loadEmailStats() {
    $.ajax({
        url: '{{ route("inventory.settings.notifications.email-stats") }}',
        method: 'GET',
        success: function(response) {
            const stats = response.stats;
            const html = `
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-1">${stats.sent_today}</h4>
                        <small class="text-muted">Sent Today</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1">${stats.delivered_today}</h4>
                        <small class="text-muted">Delivered</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h5 class="text-info mb-1">${stats.sent_week}</h5>
                        <small class="text-muted">This Week</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-warning mb-1">${stats.failed_week}</h5>
                        <small class="text-muted">Failed</small>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Delivery Rate</span>
                        <span class="text-success">${stats.delivery_rate}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: ${stats.delivery_rate}%"></div>
                    </div>
                </div>
            `;
            $('#emailStats').html(html);
            
            // Load recent activity
            loadRecentEmailActivity(response.recent_activity);
        },
        error: function() {
            $('#emailStats').html('<div class="text-center text-muted">Failed to load statistics</div>');
        }
    });
}

// Load recent email activity
function loadRecentEmailActivity(activities) {
    let html = '';
    if (activities && activities.length > 0) {
        activities.forEach(activity => {
            const statusClass = activity.status === 'delivered' ? 'success' : 
                               activity.status === 'failed' ? 'danger' : 'warning';
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <small class="text-muted">${activity.recipient}</small><br>
                        <small class="text-truncate" style="max-width: 150px;">${activity.subject}</small>
                    </div>
                    <span class="badge bg-${statusClass}">${activity.status}</span>
                </div>
            `;
        });
    } else {
        html = '<div class="text-center text-muted">No recent activity</div>';
    }
    $('#recentEmailActivity').html(html);
}

// Load SMS statistics
function loadSmsStats() {
    $.ajax({
        url: '{{ route("inventory.settings.notifications.sms-stats") }}',
        method: 'GET',
        success: function(response) {
            const stats = response.stats;
            const html = `
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-1">${stats.sent_today}</h4>
                        <small class="text-muted">Sent Today</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1">${stats.delivered_today}</h4>
                        <small class="text-muted">Delivered</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h5 class="text-info mb-1">${stats.sent_week}</h5>
                        <small class="text-muted">This Week</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-warning mb-1">${stats.failed_week}</h5>
                        <small class="text-muted">Failed</small>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Delivery Rate</span>
                        <span class="text-success">${stats.delivery_rate}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: ${stats.delivery_rate}%"></div>
                    </div>
                </div>
            `;
            $('#smsStats').html(html);
            
            // Load SMS balance
            loadSmsBalance(response.balance);
        },
        error: function() {
            $('#smsStats').html('<div class="text-center text-muted">Failed to load statistics</div>');
        }
    });
}

// Load SMS balance
function loadSmsBalance(balance) {
    const html = `
        <div class="text-center">
            <h4 class="text-success mb-1">$${balance.amount}</h4>
            <small class="text-muted">Current Balance</small>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>SMS Credits</span>
            <span class="text-info">${balance.credits}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>Cost per SMS</span>
            <span class="text-muted">$${balance.cost_per_sms}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <span>Est. Messages</span>
            <span class="text-primary">${balance.estimated_messages}</span>
        </div>
    `;
    $('#smsBalance').html(html);
}

// Load push statistics
function loadPushStats() {
    $.ajax({
        url: '{{ route("inventory.settings.notifications.push-stats") }}',
        method: 'GET',
        success: function(response) {
            const stats = response.stats;
            const html = `
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-1">${stats.sent_today}</h4>
                        <small class="text-muted">Sent Today</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success mb-1">${stats.delivered_today}</h4>
                        <small class="text-muted">Delivered</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h5 class="text-info mb-1">${stats.subscribers}</h5>
                        <small class="text-muted">Subscribers</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-warning mb-1">${stats.click_rate}%</h5>
                        <small class="text-muted">Click Rate</small>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Delivery Rate</span>
                        <span class="text-success">${stats.delivery_rate}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: ${stats.delivery_rate}%"></div>
                    </div>
                </div>
            `;
            $('#pushStats').html(html);
        },
        error: function() {
            $('#pushStats').html('<div class="text-center text-muted">Failed to load statistics</div>');
        }
    });
}

// Load channel statistics
function loadChannelStats() {
    $.ajax({
        url: '{{ route("inventory.settings.notifications.channel-stats") }}',
        method: 'GET',
        success: function(response) {
            const stats = response.stats;
            const html = `
                <div class="row text-center">
                    <div class="col-4">
                        <h5 class="text-primary mb-1">${stats.email_sent}</h5>
                        <small class="text-muted">Email</small>
                    </div>
                    <div class="col-4">
                        <h5 class="text-success mb-1">${stats.sms_sent}</h5>
                        <small class="text-muted">SMS</small>
                    </div>
                    <div class="col-4">
                        <h5 class="text-info mb-1">${stats.push_sent}</h5>
                        <small class="text-muted">Push</small>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <h6 class="text-muted mb-3">Channel Performance</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Email</span>
                        <span class="text-success">${stats.email_performance}%</span>
                    </div>
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: ${stats.email_performance}%"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>SMS</span>
                        <span class="text-success">${stats.sms_performance}%</span>
                    </div>
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: ${stats.sms_performance}%"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Push</span>
                        <span class="text-warning">${stats.push_performance}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: ${stats.push_performance}%"></div>
                    </div>
                </div>
            `;
            $('#channelStats').html(html);
        },
        error: function() {
            $('#channelStats').html('<div class="text-center text-muted">Failed to load statistics</div>');
        }
    });
}

// Load notification templates
function loadNotificationTemplates() {
    $.ajax({
        url: '{{ route("inventory.settings.notifications.templates") }}',
        method: 'GET',
        success: function(response) {
            let html = '';
            if (response.templates && response.templates.length > 0) {
                response.templates.forEach(template => {
                    const statusClass = template.status === 'active' ? 'success' : 'secondary';
                    html += `
                        <tr>
                            <td>${template.name}</td>
                            <td><span class="badge bg-primary">${template.type}</span></td>
                            <td class="text-truncate" style="max-width: 200px;">${template.subject}</td>
                            <td><span class="badge bg-${statusClass}">${template.status}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editTemplate(${template.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-info" onclick="previewTemplate(${template.id})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteTemplate(${template.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="5" class="text-center text-muted">No templates found</td></tr>';
            }
            $('#templatesTable').html(html);
        },
        error: function() {
            $('#templatesTable').html('<tr><td colspan="5" class="text-center text-muted">Failed to load templates</td></tr>');
        }
    });
}

// Load notification channels
function loadNotificationChannels() {
    $.ajax({
        url: '{{ route("inventory.settings.notifications.channels") }}',
        method: 'GET',
        success: function(response) {
            let html = '';
            if (response.channels && response.channels.length > 0) {
                response.channels.forEach(channel => {
                    const statusClass = channel.status === 'active' ? 'success' : 'secondary';
                    html += `
                        <tr>
                            <td>${channel.name}</td>
                            <td><span class="badge bg-info">${channel.type}</span></td>
                            <td>${channel.recipients_count} recipients</td>
                            <td><span class="badge bg-${statusClass}">${channel.status}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editChannel(${channel.id})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-info" onclick="testChannel(${channel.id})">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteChannel(${channel.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="5" class="text-center text-muted">No channels found</td></tr>';
            }
            $('#channelsTable').html(html);
        },
        error: function() {
            $('#channelsTable').html('<tr><td colspan="5" class="text-center text-muted">Failed to load channels</td></tr>');
        }
    });
}

// Load notification logs
function loadNotificationLogs() {
    const filters = {
        dateRange: $('#logDateRange').val(),
        type: $('#logType').val(),
        status: $('#logStatus').val(),
        search: $('#logSearch').val()
    };
    
    $.ajax({
        url: '{{ route("inventory.settings.notifications.logs") }}',
        method: 'GET',
        data: filters,
        success: function(response) {
            let html = '';
            if (response.logs && response.logs.data.length > 0) {
                response.logs.data.forEach(log => {
                    const statusClass = log.status === 'delivered' ? 'success' : 
                                       log.status === 'failed' ? 'danger' : 'warning';
                    const typeClass = log.type === 'email' ? 'primary' : 
                                     log.type === 'sms' ? 'success' : 'info';
                    html += `
                        <tr>
                            <td>${log.created_at}</td>
                            <td><span class="badge bg-${typeClass}">${log.type}</span></td>
                            <td>${log.recipient}</td>
                            <td class="text-truncate" style="max-width: 250px;">${log.subject || log.message}</td>
                            <td><span class="badge bg-${statusClass}">${log.status}</span></td>
                            <td>
                                <button class="btn btn-outline-info btn-sm" onclick="viewLogDetails(${log.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                // Update pagination
                updateLogsPagination(response.logs);
            } else {
                html = '<tr><td colspan="6" class="text-center text-muted">No logs found</td></tr>';
                $('#logsPagination').html('');
            }
            $('#logsTable').html(html);
        },
        error: function() {
            $('#logsTable').html('<tr><td colspan="6" class="text-center text-muted">Failed to load logs</td></tr>');
        }
    });
}

// Update logs pagination
function updateLogsPagination(logs) {
    let html = '';
    
    // Previous page
    if (logs.prev_page_url) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadLogsPage(${logs.current_page - 1})">Previous</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
    }
    
    // Page numbers
    for (let i = 1; i <= logs.last_page; i++) {
        if (i === logs.current_page) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadLogsPage(${i})">${i}</a></li>`;
        }
    }
    
    // Next page
    if (logs.next_page_url) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadLogsPage(${logs.current_page + 1})">Next</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
    }
    
    $('#logsPagination').html(html);
}

// Load specific page of logs
function loadLogsPage(page) {
    // Update current page and reload logs
    window.currentLogsPage = page;
    loadNotificationLogs();
}

// Apply log filters
function applyLogFilters() {
    loadNotificationLogs();
}

// Clear log filters
function clearLogFilters() {
    $('#logDateRange').val('week');
    $('#logType').val('all');
    $('#logStatus').val('all');
    $('#logSearch').val('');
    loadNotificationLogs();
}

// Search logs
function searchLogs() {
    loadNotificationLogs();
}

// Refresh logs
function refreshLogs() {
    loadNotificationLogs();
}

// Export logs
function exportLogs() {
    const filters = {
        dateRange: $('#logDateRange').val(),
        type: $('#logType').val(),
        status: $('#logStatus').val(),
        search: $('#logSearch').val()
    };
    
    window.open('{{ route("inventory.settings.notifications.export-logs") }}?' + $.param(filters));
}

// Test notifications
function testNotifications() {
    $('#testNotificationModal').modal('show');
}

// Send test notification
function sendTestNotification() {
    const type = $('#testNotificationType').val();
    const recipient = $('#testRecipient').val();
    const message = $('#testMessage').val();
    
    if (!recipient || !message) {
        showNotification('Please fill in all fields.', 'warning');
        return;
    }
    
    $.ajax({
        url: '{{ route("inventory.settings.notifications.send-test") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            type: type,
            recipient: recipient,
            message: message
        },
        success: function(response) {
            showNotification('Test notification sent successfully!', 'success');
            $('#testNotificationModal').modal('hide');
        },
        error: function(xhr) {
            showNotification('Failed to send test notification: ' + xhr.responseJSON.message, 'error');
        }
    });
}

// Export notification configuration
function exportNotificationConfig() {
    window.open('{{ route("inventory.settings.notifications.export-config") }}');
}

// Create new template
function createNewTemplate() {
    $('#templateEditor').show();
    $('#templateForm')[0].reset();
    $('#templateName').focus();
}

// Edit template
function editTemplate(templateId) {
    $.ajax({
        url: `{{ route("inventory.settings.notifications.templates") }}/${templateId}`,
        method: 'GET',
        success: function(response) {
            const template = response.template;
            $('#templateName').val(template.name);
            $('#templateType').val(template.type);
            $('#templateSubject').val(template.subject);
            $('#templateContent').val(template.content);
            $('#templateEditor').show();
            window.editingTemplateId = templateId;
        },
        error: function(xhr) {
            showNotification('Failed to load template: ' + xhr.responseJSON.message, 'error');
        }
    });
}

// Save template
function saveTemplate() {
    const templateData = {
        name: $('#templateName').val(),
        type: $('#templateType').val(),
        subject: $('#templateSubject').val(),
        content: $('#templateContent').val()
    };
    
    if (!templateData.name || !templateData.type || !templateData.subject || !templateData.content) {
        showNotification('Please fill in all fields.', 'warning');
        return;
    }
    
    const url = window.editingTemplateId ? 
        `{{ route("inventory.settings.notifications.templates") }}/${window.editingTemplateId}` :
        '{{ route("inventory.settings.notifications.templates") }}';
    
    const method = window.editingTemplateId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        data: {
            _token: '{{ csrf_token() }}',
            ...templateData
        },
        success: function(response) {
            showNotification('Template saved successfully!', 'success');
            $('#templateEditor').hide();
            loadNotificationTemplates();
            window.editingTemplateId = null;
        },
        error: function(xhr) {
            showNotification('Failed to save template: ' + xhr.responseJSON.message, 'error');
        }
    });
}

// Preview template
function previewTemplate(templateId = null) {
    let templateData;
    
    if (templateId) {
        // Preview existing template
        $.ajax({
            url: `{{ route("inventory.settings.notifications.templates") }}/${templateId}/preview`,
            method: 'GET',
            success: function(response) {
                showTemplatePreview(response.preview);
            }
        });
    } else {
        // Preview current form data
        templateData = {
            name: $('#templateName').val(),
            type: $('#templateType').val(),
            subject: $('#templateSubject').val(),
            content: $('#templateContent').val()
        };
        
        $.ajax({
            url: '{{ route("inventory.settings.notifications.templates.preview") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ...templateData
            },
            success: function(response) {
                showTemplatePreview(response.preview);
            }
        });
    }
}

// Show template preview
function showTemplatePreview(preview) {
    const html = `
        <h6>${preview.subject}</h6>
        <hr>
        <div>${preview.content}</div>
    `;
    $('#previewContent').html(html);
    $('#templatePreview').show();
}

// Cancel template edit
function cancelTemplateEdit() {
    $('#templateEditor').hide();
    $('#templatePreview').hide();
    window.editingTemplateId = null;
}

// Delete template
function deleteTemplate(templateId) {
    if (!confirm('Are you sure you want to delete this template?')) {
        return;
    }
    
    $.ajax({
        url: `{{ route("inventory.settings.notifications.templates") }}/${templateId}`,
        method: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            showNotification('Template deleted successfully!', 'success');
            loadNotificationTemplates();
        },
        error: function(xhr) {
            showNotification('Failed to delete template: ' + xhr.responseJSON.message, 'error');
        }
    });
}

// Add notification channel
function addNotificationChannel() {
    // Implementation for adding notification channel
    showNotification('Channel management feature coming soon!', 'info');
}

// Edit channel
function editChannel(channelId) {
    // Implementation for editing channel
    showNotification('Channel editing feature coming soon!', 'info');
}

// Test channel
function testChannel(channelId) {
    // Implementation for testing channel
    showNotification('Channel testing feature coming soon!', 'info');
}

// Delete channel
function deleteChannel(channelId) {
    if (!confirm('Are you sure you want to delete this channel?')) {
        return;
    }
    
    // Implementation for deleting channel
    showNotification('Channel deletion feature coming soon!', 'info');
}

// View log details
function viewLogDetails(logId) {
    $.ajax({
        url: `{{ route("inventory.settings.notifications.logs") }}/${logId}`,
        method: 'GET',
        success: function(response) {
            const log = response.log;
            const modalHtml = `
                <div class="modal fade" id="logDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Notification Log Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Type:</strong> ${log.type}<br>
                                        <strong>Status:</strong> <span class="badge bg-${log.status === 'delivered' ? 'success' : log.status === 'failed' ? 'danger' : 'warning'}">${log.status}</span><br>
                                        <strong>Recipient:</strong> ${log.recipient}<br>
                                        <strong>Sent At:</strong> ${log.created_at}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Subject:</strong> ${log.subject || 'N/A'}<br>
                                        <strong>Channel:</strong> ${log.channel || 'Default'}<br>
                                        <strong>Attempts:</strong> ${log.attempts || 1}<br>
                                        <strong>Response:</strong> ${log.response || 'N/A'}
                                    </div>
                                </div>
                                <hr>
                                <strong>Message:</strong>
                                <div class="border p-3 mt-2" style="background-color: #f8f9fa;">
                                    ${log.message}
                                </div>
                                ${log.error ? `
                                <hr>
                                <strong>Error Details:</strong>
                                <div class="border p-3 mt-2 text-danger" style="background-color: #fff5f5;">
                                    ${log.error}
                                </div>
                                ` : ''}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                ${log.status === 'failed' ? '<button type="button" class="btn btn-primary" onclick="retryNotification(' + log.id + ')">Retry</button>' : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            $('#logDetailsModal').remove();
            
            // Add new modal to body
            $('body').append(modalHtml);
            
            // Show modal
            $('#logDetailsModal').modal('show');
        },
        error: function(xhr) {
            showNotification('Failed to load log details: ' + xhr.responseJSON.message, 'error');
        }
    });
}

// Retry failed notification
function retryNotification(logId) {
    $.ajax({
        url: `{{ route("inventory.settings.notifications.logs") }}/${logId}/retry`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            showNotification('Notification retry initiated!', 'success');
            $('#logDetailsModal').modal('hide');
            loadNotificationLogs();
        },
        error: function(xhr) {
            showNotification('Failed to retry notification: ' + xhr.responseJSON.message, 'error');
        }
    });
}

// Show notification
function showNotification(message, type = 'info') {
    const alertClass = type === 'error' ? 'danger' : type;
    const alertHtml = `
        <div class="alert alert-${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Prevent accidental page leave with unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (window.hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>
@endsection