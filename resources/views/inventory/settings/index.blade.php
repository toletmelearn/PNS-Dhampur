@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-cog mr-2"></i>System Settings
            </h1>
            <p class="mb-0 text-muted">Configure and manage your inventory system</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" onclick="exportSettings()">
                <i class="fas fa-download mr-1"></i>Export Settings
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="importSettings()">
                <i class="fas fa-upload mr-1"></i>Import Settings
            </button>
            <button type="button" class="btn btn-success" onclick="saveAllSettings()">
                <i class="fas fa-save mr-1"></i>Save All Changes
            </button>
        </div>
    </div>

    <!-- Settings Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <ul class="nav nav-pills nav-fill" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="general-tab" data-toggle="pill" href="#general" role="tab">
                                <i class="fas fa-cog mr-2"></i>General
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="inventory-tab" data-toggle="pill" href="#inventory" role="tab">
                                <i class="fas fa-boxes mr-2"></i>Inventory
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="notifications-tab" data-toggle="pill" href="#notifications" role="tab">
                                <i class="fas fa-bell mr-2"></i>Notifications
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="security-tab" data-toggle="pill" href="#security" role="tab">
                                <i class="fas fa-shield-alt mr-2"></i>Security
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="backup-tab" data-toggle="pill" href="#backup" role="tab">
                                <i class="fas fa-database mr-2"></i>Backup
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="system-tab" data-toggle="pill" href="#system" role="tab">
                                <i class="fas fa-server mr-2"></i>System
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Content -->
    <div class="tab-content" id="settingsTabContent">
        <!-- General Settings -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-building mr-2"></i>Company Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="companyForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="companyName">Company Name *</label>
                                            <input type="text" class="form-control" id="companyName" name="company_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="companyEmail">Company Email</label>
                                            <input type="email" class="form-control" id="companyEmail" name="company_email">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="companyPhone">Phone Number</label>
                                            <input type="tel" class="form-control" id="companyPhone" name="company_phone">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="companyWebsite">Website</label>
                                            <input type="url" class="form-control" id="companyWebsite" name="company_website">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="companyAddress">Address</label>
                                    <textarea class="form-control" id="companyAddress" name="company_address" rows="3"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="timezone">Timezone</label>
                                            <select class="form-control" id="timezone" name="timezone">
                                                <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                                                <option value="UTC">UTC</option>
                                                <option value="America/New_York">America/New_York (EST)</option>
                                                <option value="Europe/London">Europe/London (GMT)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency">Default Currency</label>
                                            <select class="form-control" id="currency" name="currency">
                                                <option value="INR">Indian Rupee (₹)</option>
                                                <option value="USD">US Dollar ($)</option>
                                                <option value="EUR">Euro (€)</option>
                                                <option value="GBP">British Pound (£)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-info">
                                <i class="fas fa-palette mr-2"></i>Application Appearance
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="appearanceForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="appName">Application Name</label>
                                            <input type="text" class="form-control" id="appName" name="app_name" value="PNS Inventory">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="appTheme">Theme</label>
                                            <select class="form-control" id="appTheme" name="app_theme">
                                                <option value="light">Light Theme</option>
                                                <option value="dark">Dark Theme</option>
                                                <option value="auto">Auto (System)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="primaryColor">Primary Color</label>
                                            <input type="color" class="form-control" id="primaryColor" name="primary_color" value="#4e73df">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dateFormat">Date Format</label>
                                            <select class="form-control" id="dateFormat" name="date_format">
                                                <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                                <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                                <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                                <option value="DD-MM-YYYY">DD-MM-YYYY</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="appLogo">Application Logo</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="appLogo" name="app_logo" accept="image/*">
                                        <label class="custom-file-label" for="appLogo">Choose logo file...</label>
                                    </div>
                                    <small class="form-text text-muted">Recommended size: 200x50px, PNG or SVG format</small>
                                    <div id="logoPreview" class="mt-2" style="display: none;">
                                        <img id="logoImage" src="" alt="Logo Preview" class="img-thumbnail" style="max-height: 60px;">
                                        <button type="button" class="btn btn-sm btn-outline-danger ml-2" onclick="removeLogo()">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-info-circle mr-2"></i>System Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="systemInfo">
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm text-success" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted small">Loading system info...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-warning">
                                <i class="fas fa-chart-line mr-2"></i>Quick Stats
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="quickStats">
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm text-warning" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted small">Loading stats...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-danger">
                                <i class="fas fa-exclamation-triangle mr-2"></i>System Health
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="systemHealth">
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm text-danger" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted small">Checking system health...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Settings -->
        <div class="tab-pane fade" id="inventory" role="tabpanel">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-boxes mr-2"></i>Inventory Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="inventoryForm">
                                <div class="form-group">
                                    <label for="lowStockThreshold">Low Stock Threshold</label>
                                    <input type="number" class="form-control" id="lowStockThreshold" name="low_stock_threshold" min="1" value="10">
                                    <small class="form-text text-muted">Alert when stock falls below this number</small>
                                </div>
                                <div class="form-group">
                                    <label for="autoGenerateIds">Auto-generate Item IDs</label>
                                    <select class="form-control" id="autoGenerateIds" name="auto_generate_ids">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="idPrefix">Item ID Prefix</label>
                                    <input type="text" class="form-control" id="idPrefix" name="id_prefix" value="PNS" maxlength="5">
                                    <small class="form-text text-muted">Prefix for auto-generated IDs (e.g., PNS-001)</small>
                                </div>
                                <div class="form-group">
                                    <label for="defaultCategory">Default Category</label>
                                    <select class="form-control" id="defaultCategory" name="default_category">
                                        <option value="">Select default category...</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="requireApproval" name="require_approval">
                                        <label class="custom-control-label" for="requireApproval">
                                            Require approval for allocations
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="trackLocation" name="track_location">
                                        <label class="custom-control-label" for="trackLocation">
                                            Enable location tracking
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="enableBarcode" name="enable_barcode">
                                        <label class="custom-control-label" for="enableBarcode">
                                            Enable barcode scanning
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-info">
                                <i class="fas fa-wrench mr-2"></i>Maintenance Settings
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="maintenanceForm">
                                <div class="form-group">
                                    <label for="maintenanceInterval">Default Maintenance Interval (days)</label>
                                    <input type="number" class="form-control" id="maintenanceInterval" name="maintenance_interval" min="1" value="90">
                                </div>
                                <div class="form-group">
                                    <label for="maintenanceReminder">Maintenance Reminder (days before)</label>
                                    <input type="number" class="form-control" id="maintenanceReminder" name="maintenance_reminder" min="1" value="7">
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="autoSchedule" name="auto_schedule">
                                        <label class="custom-control-label" for="autoSchedule">
                                            Auto-schedule maintenance
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="emailReminders" name="email_reminders">
                                        <label class="custom-control-label" for="emailReminders">
                                            Send email reminders
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-file-alt mr-2"></i>Report Settings
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="reportForm">
                                <div class="form-group">
                                    <label for="reportFormat">Default Report Format</label>
                                    <select class="form-control" id="reportFormat" name="report_format">
                                        <option value="pdf">PDF</option>
                                        <option value="excel">Excel</option>
                                        <option value="csv">CSV</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="reportFrequency">Auto Report Frequency</label>
                                    <select class="form-control" id="reportFrequency" name="report_frequency">
                                        <option value="none">Disabled</option>
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="includeImages" name="include_images">
                                        <label class="custom-control-label" for="includeImages">
                                            Include images in reports
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="tab-pane fade" id="notifications" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-envelope mr-2"></i>Email Notifications
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="emailNotificationForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="font-weight-bold text-muted mb-3">System Notifications</h6>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="lowStockEmail" name="low_stock_email">
                                                <label class="custom-control-label" for="lowStockEmail">
                                                    Low stock alerts
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="maintenanceDueEmail" name="maintenance_due_email">
                                                <label class="custom-control-label" for="maintenanceDueEmail">
                                                    Maintenance due alerts
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="overdueReturnsEmail" name="overdue_returns_email">
                                                <label class="custom-control-label" for="overdueReturnsEmail">
                                                    Overdue return alerts
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="warrantyExpiryEmail" name="warranty_expiry_email">
                                                <label class="custom-control-label" for="warrantyExpiryEmail">
                                                    Warranty expiry alerts
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="font-weight-bold text-muted mb-3">User Notifications</h6>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="newUserEmail" name="new_user_email">
                                                <label class="custom-control-label" for="newUserEmail">
                                                    New user registration
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="allocationEmail" name="allocation_email">
                                                <label class="custom-control-label" for="allocationEmail">
                                                    Item allocation notifications
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="returnReminderEmail" name="return_reminder_email">
                                                <label class="custom-control-label" for="returnReminderEmail">
                                                    Return reminder notifications
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="reportEmail" name="report_email">
                                                <label class="custom-control-label" for="reportEmail">
                                                    Automated report delivery
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="emailFrequency">Email Frequency</label>
                                            <select class="form-control" id="emailFrequency" name="email_frequency">
                                                <option value="immediate">Immediate</option>
                                                <option value="hourly">Hourly Digest</option>
                                                <option value="daily">Daily Digest</option>
                                                <option value="weekly">Weekly Digest</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="adminEmail">Admin Email</label>
                                            <input type="email" class="form-control" id="adminEmail" name="admin_email" placeholder="admin@company.com">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-info">
                                <i class="fas fa-mobile-alt mr-2"></i>Push Notifications
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="pushNotificationForm">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="enablePush" name="enable_push">
                                        <label class="custom-control-label" for="enablePush">
                                            Enable push notifications
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="browserNotifications" name="browser_notifications">
                                        <label class="custom-control-label" for="browserNotifications">
                                            Browser notifications
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="soundAlerts" name="sound_alerts">
                                        <label class="custom-control-label" for="soundAlerts">
                                            Sound alerts
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-cog mr-2"></i>SMTP Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="smtpForm">
                                <div class="form-group">
                                    <label for="smtpHost">SMTP Host</label>
                                    <input type="text" class="form-control" id="smtpHost" name="smtp_host">
                                </div>
                                <div class="form-group">
                                    <label for="smtpPort">SMTP Port</label>
                                    <input type="number" class="form-control" id="smtpPort" name="smtp_port" value="587">
                                </div>
                                <div class="form-group">
                                    <label for="smtpUsername">Username</label>
                                    <input type="text" class="form-control" id="smtpUsername" name="smtp_username">
                                </div>
                                <div class="form-group">
                                    <label for="smtpPassword">Password</label>
                                    <input type="password" class="form-control" id="smtpPassword" name="smtp_password">
                                </div>
                                <div class="form-group">
                                    <label for="smtpEncryption">Encryption</label>
                                    <select class="form-control" id="smtpEncryption" name="smtp_encryption">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="none">None</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="testEmailConnection()">
                                    <i class="fas fa-paper-plane mr-1"></i>Test Connection
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="tab-pane fade" id="security" role="tabpanel">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-danger">
                                <i class="fas fa-lock mr-2"></i>Password Policy
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="passwordPolicyForm">
                                <div class="form-group">
                                    <label for="minPasswordLength">Minimum Password Length</label>
                                    <input type="number" class="form-control" id="minPasswordLength" name="min_password_length" min="6" max="20" value="8">
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="requireUppercase" name="require_uppercase">
                                        <label class="custom-control-label" for="requireUppercase">
                                            Require uppercase letters
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="requireNumbers" name="require_numbers">
                                        <label class="custom-control-label" for="requireNumbers">
                                            Require numbers
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="requireSpecialChars" name="require_special_chars">
                                        <label class="custom-control-label" for="requireSpecialChars">
                                            Require special characters
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="passwordExpiry">Password Expiry (days)</label>
                                    <input type="number" class="form-control" id="passwordExpiry" name="password_expiry" min="0" value="90">
                                    <small class="form-text text-muted">Set to 0 to disable password expiry</small>
                                </div>
                                <div class="form-group">
                                    <label for="passwordHistory">Password History</label>
                                    <input type="number" class="form-control" id="passwordHistory" name="password_history" min="0" max="10" value="3">
                                    <small class="form-text text-muted">Number of previous passwords to remember</small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-warning">
                                <i class="fas fa-shield-alt mr-2"></i>Session & Access Control
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="sessionForm">
                                <div class="form-group">
                                    <label for="sessionTimeout">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="sessionTimeout" name="session_timeout" min="5" value="30">
                                </div>
                                <div class="form-group">
                                    <label for="maxLoginAttempts">Max Login Attempts</label>
                                    <input type="number" class="form-control" id="maxLoginAttempts" name="max_login_attempts" min="3" max="10" value="5">
                                </div>
                                <div class="form-group">
                                    <label for="lockoutDuration">Lockout Duration (minutes)</label>
                                    <input type="number" class="form-control" id="lockoutDuration" name="lockout_duration" min="5" value="15">
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="enableTwoFactor" name="enable_two_factor">
                                        <label class="custom-control-label" for="enableTwoFactor">
                                            Enable two-factor authentication
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="forceHttps" name="force_https">
                                        <label class="custom-control-label" for="forceHttps">
                                            Force HTTPS connections
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="logSecurityEvents" name="log_security_events">
                                        <label class="custom-control-label" for="logSecurityEvents">
                                            Log security events
                                        </label>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-info">
                                <i class="fas fa-user-shield mr-2"></i>Access Permissions
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="permissionsForm">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="allowGuestAccess" name="allow_guest_access">
                                        <label class="custom-control-label" for="allowGuestAccess">
                                            Allow guest access
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="allowSelfRegistration" name="allow_self_registration">
                                        <label class="custom-control-label" for="allowSelfRegistration">
                                            Allow user self-registration
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="requireEmailVerification" name="require_email_verification">
                                        <label class="custom-control-label" for="requireEmailVerification">
                                            Require email verification
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="defaultUserRole">Default User Role</label>
                                    <select class="form-control" id="defaultUserRole" name="default_user_role">
                                        <option value="viewer">Viewer</option>
                                        <option value="employee">Employee</option>
                                        <option value="manager">Manager</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Settings -->
        <div class="tab-pane fade" id="backup" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-database mr-2"></i>Backup Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="backupForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="enableAutoBackup" name="enable_auto_backup">
                                                <label class="custom-control-label" for="enableAutoBackup">
                                                    Enable automatic backups
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="backupFrequency">Backup Frequency</label>
                                            <select class="form-control" id="backupFrequency" name="backup_frequency">
                                                <option value="daily">Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="backupTime">Backup Time</label>
                                            <input type="time" class="form-control" id="backupTime" name="backup_time" value="02:00">
                                        </div>
                                        <div class="form-group">
                                            <label for="retentionPeriod">Retention Period (days)</label>
                                            <input type="number" class="form-control" id="retentionPeriod" name="retention_period" min="1" value="30">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="backupLocation">Backup Location</label>
                                            <select class="form-control" id="backupLocation" name="backup_location">
                                                <option value="local">Local Storage</option>
                                                <option value="cloud">Cloud Storage</option>
                                                <option value="ftp">FTP Server</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="backupPath">Backup Path</label>
                                            <input type="text" class="form-control" id="backupPath" name="backup_path" value="/backups">
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="compressBackups" name="compress_backups">
                                                <label class="custom-control-label" for="compressBackups">
                                                    Compress backup files
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="encryptBackups" name="encrypt_backups">
                                                <label class="custom-control-label" for="encryptBackups">
                                                    Encrypt backup files
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="font-weight-bold text-muted mb-3">Backup Actions</h6>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-success" onclick="createBackup()">
                                                <i class="fas fa-plus mr-1"></i>Create Backup Now
                                            </button>
                                            <button type="button" class="btn btn-info" onclick="viewBackups()">
                                                <i class="fas fa-list mr-1"></i>View Backups
                                            </button>
                                            <button type="button" class="btn btn-warning" onclick="restoreBackup()">
                                                <i class="fas fa-undo mr-1"></i>Restore Backup
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-history mr-2"></i>Recent Backups
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="recentBackups">
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm text-success" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted small">Loading backups...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-info">
                                <i class="fas fa-chart-pie mr-2"></i>Storage Usage
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="storageUsage">
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm text-info" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted small">Loading storage info...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Settings -->
        <div class="tab-pane fade" id="system" role="tabpanel">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-server mr-2"></i>System Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="systemConfigForm">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="enableDebugMode" name="enable_debug_mode">
                                        <label class="custom-control-label" for="enableDebugMode">
                                            Enable debug mode
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Only enable for troubleshooting</small>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="enableMaintenance" name="enable_maintenance">
                                        <label class="custom-control-label" for="enableMaintenance">
                                            Enable maintenance mode
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="logLevel">Log Level</label>
                                    <select class="form-control" id="logLevel" name="log_level">
                                        <option value="emergency">Emergency</option>
                                        <option value="alert">Alert</option>
                                        <option value="critical">Critical</option>
                                        <option value="error">Error</option>
                                        <option value="warning">Warning</option>
                                        <option value="notice">Notice</option>
                                        <option value="info">Info</option>
                                        <option value="debug">Debug</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="maxFileSize">Max Upload File Size (MB)</label>
                                    <input type="number" class="form-control" id="maxFileSize" name="max_file_size" min="1" max="100" value="10">
                                </div>
                                <div class="form-group">
                                    <label for="cacheDriver">Cache Driver</label>
                                    <select class="form-control" id="cacheDriver" name="cache_driver">
                                        <option value="file">File</option>
                                        <option value="redis">Redis</option>
                                        <option value="memcached">Memcached</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-warning">
                                <i class="fas fa-tools mr-2"></i>System Maintenance
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <h6 class="font-weight-bold text-muted">Cache Management</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-info" onclick="clearCache('config')">
                                            <i class="fas fa-broom mr-1"></i>Config Cache
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="clearCache('route')">
                                            <i class="fas fa-broom mr-1"></i>Route Cache
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="clearCache('view')">
                                            <i class="fas fa-broom mr-1"></i>View Cache
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="clearCache('all')">
                                            <i class="fas fa-trash mr-1"></i>Clear All
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <h6 class="font-weight-bold text-muted">Log Management</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" onclick="viewLogs()">
                                            <i class="fas fa-file-alt mr-1"></i>View Logs
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" onclick="downloadLogs()">
                                            <i class="fas fa-download mr-1"></i>Download Logs
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="clearLogs()">
                                            <i class="fas fa-trash mr-1"></i>Clear Logs
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <h6 class="font-weight-bold text-muted">Database Maintenance</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-success" onclick="optimizeDatabase()">
                                            <i class="fas fa-wrench mr-1"></i>Optimize DB
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="checkDatabase()">
                                            <i class="fas fa-check mr-1"></i>Check DB
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" onclick="repairDatabase()">
                                            <i class="fas fa-tools mr-1"></i>Repair DB
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <h6 class="font-weight-bold text-muted">System Actions</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" onclick="runHealthCheck()">
                                            <i class="fas fa-heartbeat mr-1"></i>Health Check
                                        </button>
                                        <button type="button" class="btn btn-outline-info" onclick="updateSystem()">
                                            <i class="fas fa-sync mr-1"></i>Update System
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-danger">
                                <i class="fas fa-exclamation-triangle mr-2"></i>System Status
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="systemStatus">
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm text-danger" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted small">Checking system status...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Settings Modal -->
<div class="modal fade" id="importSettingsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload mr-2"></i>Import Settings
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="settingsFile">Settings File</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="settingsFile" name="settings_file" accept=".json">
                            <label class="custom-file-label" for="settingsFile">Choose settings file...</label>
                        </div>
                        <small class="form-text text-muted">Upload a JSON file exported from this system</small>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="overwriteExisting" name="overwrite_existing">
                            <label class="custom-control-label" for="overwriteExisting">
                                Overwrite existing settings
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Warning:</strong> Importing settings will modify your current configuration. Make sure to backup your current settings first.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitImport()">
                    <i class="fas fa-upload mr-1"></i>Import Settings
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Load initial data
        loadSystemInfo();
        loadQuickStats();
        loadSystemHealth();
        loadRecentBackups();
        loadStorageUsage();
        loadSystemStatus();
        loadSettings();
        loadCategories();
        
        // File input change handlers
        $('#appLogo').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#logoImage').attr('src', e.target.result);
                    $('#logoPreview').show();
                };
                reader.readAsDataURL(file);
                $(this).next('.custom-file-label').text(file.name);
            }
        });
        
        $('#settingsFile').on('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'Choose settings file...';
            $(this).next('.custom-file-label').text(fileName);
        });
    });

    function loadSettings() {
        $.get('/api/inventory/settings')
            .done(function(settings) {
                populateSettings(settings);
            })
            .fail(function() {
                showToast('Failed to load settings', 'error');
            });
    }

    function populateSettings(settings) {
        // General settings
        $('#companyName').val(settings.company_name || '');
        $('#companyEmail').val(settings.company_email || '');
        $('#companyPhone').val(settings.company_phone || '');
        $('#companyWebsite').val(settings.company_website || '');
        $('#companyAddress').val(settings.company_address || '');
        $('#timezone').val(settings.timezone || 'Asia/Kolkata');
        $('#currency').val(settings.currency || 'INR');
        $('#appName').val(settings.app_name || 'PNS Inventory');
        $('#appTheme').val(settings.app_theme || 'light');
        $('#primaryColor').val(settings.primary_color || '#4e73df');
        $('#dateFormat').val(settings.date_format || 'DD/MM/YYYY');
        
        // Inventory settings
        $('#lowStockThreshold').val(settings.low_stock_threshold || 10);
        $('#autoGenerateIds').val(settings.auto_generate_ids ? '1' : '0');
        $('#idPrefix').val(settings.id_prefix || 'PNS');
        $('#defaultCategory').val(settings.default_category || '');
        $('#requireApproval').prop('checked', settings.require_approval || false);
        $('#trackLocation').prop('checked', settings.track_location || false);
        $('#enableBarcode').prop('checked', settings.enable_barcode || false);
        
        // Maintenance settings
        $('#maintenanceInterval').val(settings.maintenance_interval || 90);
        $('#maintenanceReminder').val(settings.maintenance_reminder || 7);
        $('#autoSchedule').prop('checked', settings.auto_schedule || false);
        $('#emailReminders').prop('checked', settings.email_reminders || false);
        
        // Report settings
        $('#reportFormat').val(settings.report_format || 'pdf');
        $('#reportFrequency').val(settings.report_frequency || 'none');
        $('#includeImages').prop('checked', settings.include_images || false);
        
        // Notification settings
        $('#lowStockEmail').prop('checked', settings.low_stock_email || false);
        $('#maintenanceDueEmail').prop('checked', settings.maintenance_due_email || false);
        $('#overdueReturnsEmail').prop('checked', settings.overdue_returns_email || false);
        $('#warrantyExpiryEmail').prop('checked', settings.warranty_expiry_email || false);
        $('#newUserEmail').prop('checked', settings.new_user_email || false);
        $('#allocationEmail').prop('checked', settings.allocation_email || false);
        $('#returnReminderEmail').prop('checked', settings.return_reminder_email || false);
        $('#reportEmail').prop('checked', settings.report_email || false);
        $('#emailFrequency').val(settings.email_frequency || 'immediate');
        $('#adminEmail').val(settings.admin_email || '');
        
        // Push notifications
        $('#enablePush').prop('checked', settings.enable_push || false);
        $('#browserNotifications').prop('checked', settings.browser_notifications || false);
        $('#soundAlerts').prop('checked', settings.sound_alerts || false);
        
        // SMTP settings
        $('#