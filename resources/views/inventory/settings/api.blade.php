@extends('layouts.app')

@section('title', 'API Settings - PNS Dhampur')

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
                            <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                            <li class="breadcrumb-item active">API Settings</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">API Settings</h1>
                    <p class="text-muted">Configure external integrations, webhooks, and API access</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="testAllConnections()">
                        <i class="fas fa-plug me-1"></i> Test All Connections
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="exportAPIConfig()">
                        <i class="fas fa-download me-1"></i> Export Config
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveAllAPISettings()">
                        <i class="fas fa-save me-1"></i> Save All Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" id="apiTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="api-keys-tab" data-bs-toggle="tab" data-bs-target="#api-keys" type="button" role="tab">
                <i class="fas fa-key me-1"></i> API Keys
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="webhooks-tab" data-bs-toggle="tab" data-bs-target="#webhooks" type="button" role="tab">
                <i class="fas fa-webhook me-1"></i> Webhooks
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="integrations-tab" data-bs-toggle="tab" data-bs-target="#integrations" type="button" role="tab">
                <i class="fas fa-puzzle-piece me-1"></i> Integrations
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="rate-limiting-tab" data-bs-toggle="tab" data-bs-target="#rate-limiting" type="button" role="tab">
                <i class="fas fa-tachometer-alt me-1"></i> Rate Limiting
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="api-logs-tab" data-bs-toggle="tab" data-bs-target="#api-logs" type="button" role="tab">
                <i class="fas fa-list-alt me-1"></i> API Logs
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="documentation-tab" data-bs-toggle="tab" data-bs-target="#documentation" type="button" role="tab">
                <i class="fas fa-book me-1"></i> Documentation
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="apiTabContent">
        <!-- API Keys Tab -->
        <div class="tab-pane fade show active" id="api-keys" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">API Keys Management</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAPIKeyModal">
                                <i class="fas fa-plus me-1"></i> Generate New Key
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Key</th>
                                            <th>Permissions</th>
                                            <th>Status</th>
                                            <th>Last Used</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="apiKeysTable">
                                        <!-- API keys will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- API Configuration -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">API Configuration</h5>
                        </div>
                        <div class="card-body">
                            <form id="apiConfigForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="apiVersion" class="form-label">API Version</label>
                                            <select class="form-select" id="apiVersion">
                                                <option value="v1">Version 1.0</option>
                                                <option value="v2" selected>Version 2.0</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="apiTimeout" class="form-label">Request Timeout (seconds)</label>
                                            <input type="number" class="form-control" id="apiTimeout" value="30" min="5" max="300">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="enableCORS" checked>
                                                <label class="form-check-label" for="enableCORS">
                                                    Enable CORS
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="requireHTTPS" checked>
                                                <label class="form-check-label" for="requireHTTPS">
                                                    Require HTTPS
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="allowedOrigins" class="form-label">Allowed Origins (one per line)</label>
                                    <textarea class="form-control" id="allowedOrigins" rows="3" placeholder="https://example.com&#10;https://app.example.com">https://localhost:3000
https://app.pnsdhampur.com</textarea>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- API Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">API Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-primary mb-0" id="totalAPIKeys">0</h4>
                                        <small class="text-muted">Total Keys</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success mb-0" id="activeAPIKeys">0</h4>
                                    <small class="text-muted">Active Keys</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-info mb-0" id="todayRequests">0</h4>
                                        <small class="text-muted">Today's Requests</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-warning mb-0" id="monthlyRequests">0</h4>
                                    <small class="text-muted">Monthly Requests</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent API Activity -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recent API Activity</h5>
                        </div>
                        <div class="card-body">
                            <div id="recentAPIActivity">
                                <!-- Recent activity will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webhooks Tab -->
        <div class="tab-pane fade" id="webhooks" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Webhook Endpoints</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addWebhookModal">
                                <i class="fas fa-plus me-1"></i> Add Webhook
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>URL</th>
                                            <th>Events</th>
                                            <th>Status</th>
                                            <th>Last Triggered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="webhooksTable">
                                        <!-- Webhooks will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Webhook Events Configuration -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Available Webhook Events</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Inventory Events</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="inventoryCreated">
                                        <label class="form-check-label" for="inventoryCreated">
                                            inventory.created
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="inventoryUpdated">
                                        <label class="form-check-label" for="inventoryUpdated">
                                            inventory.updated
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="inventoryDeleted">
                                        <label class="form-check-label" for="inventoryDeleted">
                                            inventory.deleted
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="stockLow">
                                        <label class="form-check-label" for="stockLow">
                                            stock.low
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>User Events</h6>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="userCreated">
                                        <label class="form-check-label" for="userCreated">
                                            user.created
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="userUpdated">
                                        <label class="form-check-label" for="userUpdated">
                                            user.updated
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="userLogin">
                                        <label class="form-check-label" for="userLogin">
                                            user.login
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="userLogout">
                                        <label class="form-check-label" for="userLogout">
                                            user.logout
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Webhook Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Webhook Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-primary mb-0" id="totalWebhooks">0</h4>
                                        <small class="text-muted">Total Webhooks</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success mb-0" id="activeWebhooks">0</h4>
                                    <small class="text-muted">Active Webhooks</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="text-info mb-0" id="successfulDeliveries">0</h4>
                                        <small class="text-muted">Successful</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-danger mb-0" id="failedDeliveries">0</h4>
                                    <small class="text-muted">Failed</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Webhook Deliveries -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Deliveries</h5>
                        </div>
                        <div class="card-body">
                            <div id="recentWebhookDeliveries">
                                <!-- Recent deliveries will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integrations Tab -->
        <div class="tab-pane fade" id="integrations" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Third-party Integrations -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Third-party Integrations</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Slack Integration -->
                                <div class="col-md-6 mb-4">
                                    <div class="integration-card border rounded p-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="integration-icon me-3">
                                                <i class="fab fa-slack fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Slack</h6>
                                                <small class="text-muted">Team communication</small>
                                            </div>
                                            <div class="ms-auto">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="slackEnabled">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="slackWebhookURL" class="form-label">Webhook URL</label>
                                            <input type="url" class="form-control" id="slackWebhookURL" placeholder="https://hooks.slack.com/services/...">
                                        </div>
                                        <div class="mb-3">
                                            <label for="slackChannel" class="form-label">Default Channel</label>
                                            <input type="text" class="form-control" id="slackChannel" placeholder="#inventory">
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="testSlackIntegration()">
                                            <i class="fas fa-test-tube me-1"></i> Test Connection
                                        </button>
                                    </div>
                                </div>

                                <!-- Email Integration -->
                                <div class="col-md-6 mb-4">
                                    <div class="integration-card border rounded p-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="integration-icon me-3">
                                                <i class="fas fa-envelope fa-2x text-success"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Email Notifications</h6>
                                                <small class="text-muted">SMTP integration</small>
                                            </div>
                                            <div class="ms-auto">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="emailEnabled" checked>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="smtpHost" class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" id="smtpHost" value="smtp.gmail.com">
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label for="smtpPort" class="form-label">Port</label>
                                                    <input type="number" class="form-control" id="smtpPort" value="587">
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="mb-3">
                                                    <label for="smtpEncryption" class="form-label">Encryption</label>
                                                    <select class="form-select" id="smtpEncryption">
                                                        <option value="tls" selected>TLS</option>
                                                        <option value="ssl">SSL</option>
                                                        <option value="none">None</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="testEmailIntegration()">
                                            <i class="fas fa-test-tube me-1"></i> Test Connection
                                        </button>
                                    </div>
                                </div>

                                <!-- SMS Integration -->
                                <div class="col-md-6 mb-4">
                                    <div class="integration-card border rounded p-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="integration-icon me-3">
                                                <i class="fas fa-sms fa-2x text-warning"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">SMS Notifications</h6>
                                                <small class="text-muted">Twilio integration</small>
                                            </div>
                                            <div class="ms-auto">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="smsEnabled">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="twilioSID" class="form-label">Account SID</label>
                                            <input type="text" class="form-control" id="twilioSID" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                        </div>
                                        <div class="mb-3">
                                            <label for="twilioToken" class="form-label">Auth Token</label>
                                            <input type="password" class="form-control" id="twilioToken" placeholder="••••••••••••••••••••••••••••••••">
                                        </div>
                                        <div class="mb-3">
                                            <label for="twilioPhone" class="form-label">From Phone Number</label>
                                            <input type="tel" class="form-control" id="twilioPhone" placeholder="+1234567890">
                                        </div>
                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="testSMSIntegration()">
                                            <i class="fas fa-test-tube me-1"></i> Test Connection
                                        </button>
                                    </div>
                                </div>

                                <!-- Google Sheets Integration -->
                                <div class="col-md-6 mb-4">
                                    <div class="integration-card border rounded p-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="integration-icon me-3">
                                                <i class="fab fa-google fa-2x text-info"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Google Sheets</h6>
                                                <small class="text-muted">Data synchronization</small>
                                            </div>
                                            <div class="ms-auto">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="googleSheetsEnabled">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="googleCredentials" class="form-label">Service Account JSON</label>
                                            <textarea class="form-control" id="googleCredentials" rows="3" placeholder="Paste your service account JSON here"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="googleSheetId" class="form-label">Sheet ID</label>
                                            <input type="text" class="form-control" id="googleSheetId" placeholder="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms">
                                        </div>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="testGoogleSheetsIntegration()">
                                            <i class="fas fa-test-tube me-1"></i> Test Connection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Integration Status -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Integration Status</h5>
                        </div>
                        <div class="card-body">
                            <div id="integrationStatus">
                                <!-- Integration status will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Integration Logs -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Integration Activity</h5>
                        </div>
                        <div class="card-body">
                            <div id="integrationLogs">
                                <!-- Integration logs will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rate Limiting Tab -->
        <div class="tab-pane fade" id="rate-limiting" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Rate Limiting Configuration</h5>
                        </div>
                        <div class="card-body">
                            <form id="rateLimitingForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="globalRateLimit" class="form-label">Global Rate Limit (requests per minute)</label>
                                            <input type="number" class="form-control" id="globalRateLimit" value="1000" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="perKeyRateLimit" class="form-label">Per API Key Limit (requests per minute)</label>
                                            <input type="number" class="form-control" id="perKeyRateLimit" value="100" min="1">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="burstLimit" class="form-label">Burst Limit (requests per second)</label>
                                            <input type="number" class="form-control" id="burstLimit" value="10" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="rateLimitWindow" class="form-label">Rate Limit Window</label>
                                            <select class="form-select" id="rateLimitWindow">
                                                <option value="minute" selected>Per Minute</option>
                                                <option value="hour">Per Hour</option>
                                                <option value="day">Per Day</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enableRateLimiting" checked>
                                        <label class="form-check-label" for="enableRateLimiting">
                                            Enable Rate Limiting
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="blockOnExceed" checked>
                                        <label class="form-check-label" for="blockOnExceed">
                                            Block requests when limit exceeded
                                        </label>
                                    </div>
                                </div>
                            </form>

                            <!-- Custom Rate Limits -->
                            <hr>
                            <h6>Custom Rate Limits by Endpoint</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Endpoint</th>
                                            <th>Method</th>
                                            <th>Rate Limit</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="customRateLimitsTable">
                                        <tr>
                                            <td>/api/inventory</td>
                                            <td>GET</td>
                                            <td>200/min</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editRateLimit('/api/inventory', 'GET')">Edit</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>/api/inventory</td>
                                            <td>POST</td>
                                            <td>50/min</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editRateLimit('/api/inventory', 'POST')">Edit</button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>/api/users</td>
                                            <td>GET</td>
                                            <td>100/min</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editRateLimit('/api/users', 'GET')">Edit</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addCustomRateLimit()">
                                <i class="fas fa-plus me-1"></i> Add Custom Rate Limit
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Rate Limiting Statistics -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Rate Limiting Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-12 mb-3">
                                    <h4 class="text-primary mb-0" id="currentRequestRate">0</h4>
                                    <small class="text-muted">Current Requests/Min</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h5 class="text-success mb-0" id="allowedRequests">0</h5>
                                        <small class="text-muted">Allowed</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h5 class="text-danger mb-0" id="blockedRequests">0</h5>
                                    <small class="text-muted">Blocked</small>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <canvas id="rateLimitChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Rate Limited IPs -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Top Rate Limited IPs</h5>
                        </div>
                        <div class="card-body">
                            <div id="topRateLimitedIPs">
                                <!-- Top rate limited IPs will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Logs Tab -->
        <div class="tab-pane fade" id="api-logs" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="mb-0">API Request Logs</h5>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshAPILogs()">
                                            <i class="fas fa-sync-alt me-1"></i> Refresh
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportAPILogs()">
                                            <i class="fas fa-download me-1"></i> Export
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Log Filters -->
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm" id="logStatusFilter">
                                        <option value="">All Status</option>
                                        <option value="200">200 - OK</option>
                                        <option value="400">400 - Bad Request</option>
                                        <option value="401">401 - Unauthorized</option>
                                        <option value="403">403 - Forbidden</option>
                                        <option value="404">404 - Not Found</option>
                                        <option value="429">429 - Too Many Requests</option>
                                        <option value="500">500 - Server Error</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select form-select-sm" id="logMethodFilter">
                                        <option value="">All Methods</option>
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="DELETE">DELETE</option>
                                        <option value="PATCH">PATCH</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control form-control-sm" id="logDateFrom">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" class="form-control form-control-sm" id="logDateTo">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control form-control-sm" id="logSearchInput" placeholder="Search endpoint, IP, or API key...">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="applyAPILogFilters()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Logs Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Method</th>
                                            <th>Endpoint</th>
                                            <th>Status</th>
                                            <th>Response Time</th>
                                            <th>IP Address</th>
                                            <th>API Key</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="apiLogsTable">
                                        <!-- API logs will be loaded here -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <nav aria-label="API logs pagination">
                                <ul class="pagination pagination-sm justify-content-center" id="apiLogsPagination">
                                    <!-- Pagination will be loaded here -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documentation Tab -->
        <div class="tab-pane fade" id="documentation" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">API Documentation</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6>Base URL</h6>
                                <code id="apiBaseURL">https://api.pnsdhampur.com/v2</code>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('apiBaseURL')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>

                            <div class="mb-4">
                                <h6>Authentication</h6>
                                <p>Include your API key in the request headers:</p>
                                <pre class="bg-light p-3 rounded"><code>Authorization: Bearer YOUR_API_KEY</code></pre>
                            </div>

                            <div class="mb-4">
                                <h6>Available Endpoints</h6>
                                <div class="accordion" id="apiEndpointsAccordion">
                                    <!-- Inventory Endpoints -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="inventoryEndpointsHeading">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#inventoryEndpoints">
                                                Inventory Management
                                            </button>
                                        </h2>
                                        <div id="inventoryEndpoints" class="accordion-collapse collapse show" data-bs-parent="#apiEndpointsAccordion">
                                            <div class="accordion-body">
                                                <div class="endpoint-item mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="badge bg-success me-2">GET</span>
                                                        <code>/api/inventory</code>
                                                    </div>
                                                    <p class="mb-1">Get all inventory items</p>
                                                    <small class="text-muted">Parameters: page, limit, search, category</small>
                                                </div>
                                                <div class="endpoint-item mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="badge bg-primary me-2">POST</span>
                                                        <code>/api/inventory</code>
                                                    </div>
                                                    <p class="mb-1">Create new inventory item</p>
                                                    <small class="text-muted">Body: name, description, quantity, price, category</small>
                                                </div>
                                                <div class="endpoint-item mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="badge bg-warning me-2">PUT</span>
                                                        <code>/api/inventory/{id}</code>
                                                    </div>
                                                    <p class="mb-1">Update inventory item</p>
                                                    <small class="text-muted">Body: name, description, quantity, price, category</small>
                                                </div>
                                                <div class="endpoint-item">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="badge bg-danger me-2">DELETE</span>
                                                        <code>/api/inventory/{id}</code>
                                                    </div>
                                                    <p class="mb-1">Delete inventory item</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- User Endpoints -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="userEndpointsHeading">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#userEndpoints">
                                                User Management
                                            </button>
                                        </h2>
                                        <div id="userEndpoints" class="accordion-collapse collapse" data-bs-parent="#apiEndpointsAccordion">
                                            <div class="accordion-body">
                                                <div class="endpoint-item mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="badge bg-success me-2">GET</span>
                                                        <code>/api/users</code>
                                                    </div>
                                                    <p class="mb-1">Get all users</p>
                                                    <small class="text-muted">Parameters: page, limit, search, role</small>
                                                </div>
                                                <div class="endpoint-item mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="badge bg-primary me-2">POST</span>
                                                        <code>/api/users</code>
                                                    </div>
                                                    <p class="mb-1">Create new user</p>
                                                    <small class="text-muted">Body: name, email, password, role</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reports Endpoints -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="reportsEndpointsHeading">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#reportsEndpoints">
                                                Reports & Analytics
                                            </button>
                                        </h2>
                                        <div id="reportsEndpoints" class="accordion-collapse collapse" data-bs-parent="#apiEndpointsAccordion">
                                            <div class="accordion-body">
                                                <div class="endpoint-item mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="badge bg-success me-2">GET</span>
                                                        <code>/api/reports/inventory</code>
                                                    </div>
                                                    <p class="mb-1">Get inventory reports</p>
                                                    <small class="text-muted">Parameters: type, date_from, date_to</small>
                                                </div>
                                                <div class="endpoint-item">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="badge bg-success me-2">GET</span>
                                                        <code>/api/reports/analytics</code>
                                                    </div>
                                                    <p class="mb-1">Get analytics data</p>
                                                    <small class="text-muted">Parameters: metric, period</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6>Response Format</h6>
                                <p>All API responses follow this format:</p>
                                <pre class="bg-light p-3 rounded"><code>{
  "success": true,
  "data": {...},
  "message": "Success message",
  "meta": {
    "pagination": {...}
  }
}</code></pre>
                            </div>

                            <div class="mb-4">
                                <h6>Error Codes</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Code</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>200</code></td>
                                                <td>Success</td>
                                            </tr>
                                            <tr>
                                                <td><code>400</code></td>
                                                <td>Bad Request - Invalid parameters</td>
                                            </tr>
                                            <tr>
                                                <td><code>401</code></td>
                                                <td>Unauthorized - Invalid API key</td>
                                            </tr>
                                            <tr>
                                                <td><code>403</code></td>
                                                <td>Forbidden - Insufficient permissions</td>
                                            </tr>
                                            <tr>
                                                <td><code>404</code></td>
                                                <td>Not Found - Resource not found</td>
                                            </tr>
                                            <tr>
                                                <td><code>429</code></td>
                                                <td>Too Many Requests - Rate limit exceeded</td>
                                            </tr>
                                            <tr>
                                                <td><code>500</code></td>
                                                <td>Internal Server Error</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- API Testing Tool -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">API Testing Tool</h5>
                        </div>
                        <div class="card-body">
                            <form id="apiTestForm">
                                <div class="mb-3">
                                    <label for="testMethod" class="form-label">Method</label>
                                    <select class="form-select" id="testMethod">
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="DELETE">DELETE</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="testEndpoint" class="form-label">Endpoint</label>
                                    <input type="text" class="form-control" id="testEndpoint" placeholder="/api/inventory" value="/api/inventory">
                                </div>
                                <div class="mb-3">
                                    <label for="testAPIKey" class="form-label">API Key</label>
                                    <select class="form-select" id="testAPIKey">
                                        <option value="">Select API Key</option>
                                        <!-- API keys will be loaded here -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="testBody" class="form-label">Request Body (JSON)</label>
                                    <textarea class="form-control" id="testBody" rows="4" placeholder='{"name": "Test Item"}'></textarea>
                                </div>
                                <button type="button" class="btn btn-primary w-100" onclick="testAPIEndpoint()">
                                    <i class="fas fa-play me-1"></i> Send Request
                                </button>
                            </form>

                            <div id="apiTestResult" class="mt-3" style="display: none;">
                                <h6>Response:</h6>
                                <pre class="bg-light p-3 rounded" id="apiTestResponse"></pre>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Links</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="#" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-book me-1"></i> Full API Documentation
                                </a>
                                <a href="#" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-code me-1"></i> Code Examples
                                </a>
                                <a href="#" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-question-circle me-1"></i> API Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add API Key Modal -->
<div class="modal fade" id="addAPIKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate New API Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAPIKeyForm">
                    <div class="mb-3">
                        <label for="apiKeyName" class="form-label">Key Name</label>
                        <input type="text" class="form-control" id="apiKeyName" required>
                    </div>
                    <div class="mb-3">
                        <label for="apiKeyDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="apiKeyDescription" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="readPermission" checked>
                            <label class="form-check-label" for="readPermission">Read</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="writePermission">
                            <label class="form-check-label" for="writePermission">Write</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="deletePermission">
                            <label class="form-check-label" for="deletePermission">Delete</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="apiKeyExpiry" class="form-label">Expiry Date (optional)</label>
                        <input type="date" class="form-control" id="apiKeyExpiry">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="generateAPIKey()">Generate Key</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Webhook Modal -->
<div class="modal fade" id="addWebhookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Webhook</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addWebhookForm">
                    <div class="mb-3">
                        <label for="webhookName" class="form-label">Webhook Name</label>
                        <input type="text" class="form-control" id="webhookName" required>
                    </div>
                    <div class="mb-3">
                        <label for="webhookURL" class="form-label">Webhook URL</label>
                        <input type="url" class="form-control" id="webhookURL" required>
                    </div>
                    <div class="mb-3">
                        <label for="webhookSecret" class="form-label">Secret (optional)</label>
                        <input type="text" class="form-control" id="webhookSecret">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Events</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="webhookInventoryEvents">
                            <label class="form-check-label" for="webhookInventoryEvents">Inventory Events</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="webhookUserEvents">
                            <label class="form-check-label" for="webhookUserEvents">User Events</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="webhookSystemEvents">
                            <label class="form-check-label" for="webhookSystemEvents">System Events</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addWebhook()">Add Webhook</button>
            </div>
        </div>
    </div>
</div>

<style>
.integration-card {
    transition: all 0.3s ease;
}

.integration-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.integration-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.05);
    border-radius: 8px;
}

.endpoint-item {
    border-left: 3px solid #e9ecef;
    padding-left: 15px;
}

.activity-item {
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item.warning {
    background-color: #fff3cd;
    border-radius: 4px;
    padding: 8px 12px;
    margin: 4px 0;
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #6c757d;
}

.nav-tabs .nav-link.active {
    border-bottom-color: #0d6efd;
    color: #0d6efd;
    background: none;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75em;
}

pre {
    font-size: 0.875rem;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-outline-primary:hover {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.alert {
    border: none;
    border-radius: 8px;
}

.pagination .page-link {
    border: none;
    color: #6c757d;
}

.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>

<script>
// Initialize API settings when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeAPISettings();
    loadAPIKeys();
    loadWebhooks();
    loadAPIStatistics();
    loadRecentAPIActivity();
    loadWebhookStatistics();
    loadRecentWebhookDeliveries();
    loadIntegrationStatus();
    loadIntegrationLogs();
    loadRateLimitingStats();
    loadAPILogs();
    initializeRateLimitChart();
    
    // Set up event listeners
    setupAPIEventListeners();
});

// Initialize API settings
function initializeAPISettings() {
    // Load saved settings from localStorage
    const savedSettings = localStorage.getItem('apiSettings');
    if (savedSettings) {
        const settings = JSON.parse(savedSettings);
        
        // Populate form fields with saved settings
        Object.keys(settings).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                if (element.type === 'checkbox') {
                    element.checked = settings[key];
                } else {
                    element.value = settings[key];
                }
            }
        });
    }
}

// Set up event listeners
function setupAPIEventListeners() {
    // API log filters
    const logFilters = ['logStatusFilter', 'logMethodFilter', 'logDateFrom', 'logDateTo', 'logSearchInput'];
    logFilters.forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', debounce(applyAPILogFilters, 300));
        }
// Load rate limiting statistics
function loadRateLimitingStatistics() {
    document.getElementById('currentRequestRate').textContent = '45/min';
    document.getElementById('allowedRequests').textContent = '98.2%';
    document.getElementById('blockedRequests').textContent = '1.8%';

    // Initialize rate limit chart
    const ctx = document.getElementById('rateLimitChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
            datasets: [{
                label: 'Requests/Hour',
                data: [120, 190, 300, 500, 200, 300],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Load top rate limited IPs
    const ips = [
        { ip: '192.168.1.100', requests: '1,250', blocked: '45' },
        { ip: '10.0.0.50', requests: '890', blocked: '23' },
        { ip: '172.16.0.25', requests: '567', blocked: '12' },
        { ip: '203.0.113.10', requests: '234', blocked: '8' }
    ];

    const container = document.getElementById('topRateLimitedIPs');
    container.innerHTML = '';

    ips.forEach(ip => {
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-medium">${ip.ip}</div>
                    <small class="text-muted">${ip.requests} requests</small>
                </div>
                <span class="badge bg-warning">${ip.blocked} blocked</span>
            </div>
        `;
        container.appendChild(item);
    });
}

// Load API logs
function loadAPILogs() {
    const logs = [
        {
            timestamp: '2024-01-15 14:30:25',
            method: 'GET',
            endpoint: '/api/v1/inventory',
            status: 200,
            responseTime: '45ms',
            ip: '192.168.1.100',
            userAgent: 'PostmanRuntime/7.32.3'
        },
        {
            timestamp: '2024-01-15 14:29:18',
            method: 'POST',
            endpoint: '/api/v1/products',
            status: 201,
            responseTime: '120ms',
            ip: '10.0.0.50',
            userAgent: 'curl/7.68.0'
        },
        {
            timestamp: '2024-01-15 14:28:45',
            method: 'PUT',
            endpoint: '/api/v1/products/123',
            status: 404,
            responseTime: '25ms',
            ip: '172.16.0.25',
            userAgent: 'axios/0.21.1'
        },
        {
            timestamp: '2024-01-15 14:27:32',
            method: 'DELETE',
            endpoint: '/api/v1/products/456',
            status: 403,
            responseTime: '15ms',
            ip: '203.0.113.10',
            userAgent: 'fetch/1.0'
        }
    ];

    const tableBody = document.getElementById('apiLogsTable');
    tableBody.innerHTML = '';

    logs.forEach(log => {
        const row = document.createElement('tr');
        const statusClass = log.status >= 200 && log.status < 300 ? 'success' : 
                           log.status >= 400 ? 'danger' : 'warning';
        
        row.innerHTML = `
            <td>${log.timestamp}</td>
            <td>
                <span class="badge bg-${log.method === 'GET' ? 'primary' : 
                                      log.method === 'POST' ? 'success' : 
                                      log.method === 'PUT' ? 'warning' : 'danger'}">${log.method}</span>
            </td>
            <td><code>${log.endpoint}</code></td>
            <td>
                <span class="badge bg-${statusClass}">${log.status}</span>
            </td>
            <td>${log.responseTime}</td>
            <td>${log.ip}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewAPILogDetails('${log.timestamp}')">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// API Key Management Functions
function generateAPIKey() {
    const modal = new bootstrap.Modal(document.getElementById('addAPIKeyModal'));
    modal.show();
}

function addAPIKey() {
    const form = document.getElementById('addAPIKeyForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!formData.get('keyName') || !formData.get('permissions')) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }
    
    // Generate new API key
    const newKey = 'pk_' + Math.random().toString(36).substr(2, 32);
    
    showNotification('API key generated successfully!', 'success');
    
    // Close modal and refresh table
    const modal = bootstrap.Modal.getInstance(document.getElementById('addAPIKeyModal'));
    modal.hide();
    form.reset();
    loadAPIKeys();
}

function editAPIKey(keyId) {
    showNotification('Edit API key functionality would be implemented here', 'info');
}

function deleteAPIKey(keyId) {
    if (confirm('Are you sure you want to delete this API key? This action cannot be undone.')) {
        showNotification('API key deleted successfully', 'success');
        loadAPIKeys();
    }
}

function copyAPIKey(key) {
    navigator.clipboard.writeText(key).then(() => {
        showNotification('API key copied to clipboard', 'success');
    });
}

// Webhook Management Functions
function addWebhook() {
    const modal = new bootstrap.Modal(document.getElementById('addWebhookModal'));
    modal.show();
}

function saveWebhook() {
    const form = document.getElementById('addWebhookForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!formData.get('webhookName') || !formData.get('webhookUrl')) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }
    
    showNotification('Webhook added successfully!', 'success');
    
    // Close modal and refresh table
    const modal = bootstrap.Modal.getInstance(document.getElementById('addWebhookModal'));
    modal.hide();
    form.reset();
    loadWebhooks();
}

function editWebhook(webhookId) {
    showNotification('Edit webhook functionality would be implemented here', 'info');
}

function testWebhook(webhookId) {
    showNotification('Testing webhook...', 'info');
    
    // Simulate webhook test
    setTimeout(() => {
        showNotification('Webhook test completed successfully', 'success');
    }, 2000);
}

function deleteWebhook(webhookId) {
    if (confirm('Are you sure you want to delete this webhook?')) {
        showNotification('Webhook deleted successfully', 'success');
        loadWebhooks();
    }
}

// Integration Functions
function testSlackConnection() {
    const slackWebhook = document.getElementById('slackWebhookUrl').value;
    const slackChannel = document.getElementById('slackChannel').value;
    
    if (!slackWebhook) {
        showNotification('Please enter Slack webhook URL', 'error');
        return;
    }
    
    showNotification('Testing Slack connection...', 'info');
    
    // Simulate connection test
    setTimeout(() => {
        showNotification('Slack connection test successful!', 'success');
    }, 2000);
}

function testEmailConnection() {
    const smtpHost = document.getElementById('smtpHost').value;
    const smtpPort = document.getElementById('smtpPort').value;
    const smtpUsername = document.getElementById('smtpUsername').value;
    
    if (!smtpHost || !smtpPort || !smtpUsername) {
        showNotification('Please fill in all SMTP settings', 'error');
        return;
    }
    
    showNotification('Testing email connection...', 'info');
    
    // Simulate connection test
    setTimeout(() => {
        showNotification('Email connection test successful!', 'success');
    }, 2000);
}

function testSMSConnection() {
    const twilioSid = document.getElementById('twilioAccountSid').value;
    const twilioToken = document.getElementById('twilioAuthToken').value;
    
    if (!twilioSid || !twilioToken) {
        showNotification('Please enter Twilio credentials', 'error');
        return;
    }
    
    showNotification('Testing SMS connection...', 'info');
    
    // Simulate connection test
    setTimeout(() => {
        showNotification('SMS connection test successful!', 'success');
    }, 2000);
}

function testGoogleSheetsConnection() {
    const serviceAccount = document.getElementById('googleServiceAccount').value;
    const spreadsheetId = document.getElementById('googleSpreadsheetId').value;
    
    if (!serviceAccount || !spreadsheetId) {
        showNotification('Please fill in Google Sheets settings', 'error');
        return;
    }
    
    showNotification('Testing Google Sheets connection...', 'info');
    
    // Simulate connection test
    setTimeout(() => {
        showNotification('Google Sheets connection test successful!', 'success');
    }, 2000);
}

// Rate Limiting Functions
function addCustomRateLimit() {
    const endpoint = document.getElementById('customEndpoint').value;
    const limit = document.getElementById('customLimit').value;
    
    if (!endpoint || !limit) {
        showNotification('Please enter endpoint and limit', 'error');
        return;
    }
    
    showNotification('Custom rate limit added successfully', 'success');
    
    // Clear form
    document.getElementById('customEndpoint').value = '';
    document.getElementById('customLimit').value = '';
    
    // Refresh custom limits table (would be implemented)
}

// API Testing Functions
function testAPIEndpoint() {
    const method = document.getElementById('testMethod').value;
    const endpoint = document.getElementById('testEndpoint').value;
    const apiKey = document.getElementById('testAPIKey').value;
    const requestBody = document.getElementById('testRequestBody').value;
    
    if (!endpoint) {
        showNotification('Please enter an endpoint', 'error');
        return;
    }
    
    showNotification('Testing API endpoint...', 'info');
    
    // Simulate API test
    setTimeout(() => {
        const response = {
            status: 200,
            statusText: 'OK',
            headers: {
                'Content-Type': 'application/json',
                'X-RateLimit-Remaining': '99'
            },
            data: {
                success: true,
                message: 'API test successful',
                timestamp: new Date().toISOString()
            }
        };
        
        document.getElementById('apiResponse').textContent = JSON.stringify(response, null, 2);
        showNotification('API test completed successfully', 'success');
    }, 2000);
}

// Filter Functions
function applyAPILogFilters() {
    const status = document.getElementById('logStatusFilter').value;
    const method = document.getElementById('logMethodFilter').value;
    const dateFrom = document.getElementById('logDateFrom').value;
    const dateTo = document.getElementById('logDateTo').value;
    const search = document.getElementById('logSearch').value;
    
    showNotification('Applying filters...', 'info');
    
    // Simulate filtering
    setTimeout(() => {
        loadAPILogs();
        showNotification('Filters applied successfully', 'success');
    }, 1000);
}

// Utility Functions
function testAllConnections() {
    showNotification('Testing all connections...', 'info');
    
    // Simulate testing all connections
    setTimeout(() => {
        showNotification('All connection tests completed', 'success');
    }, 3000);
}

function exportAPIConfig() {
    const config = {
        apiKeys: 3,
        webhooks: 3,
        integrations: 4,
        rateLimits: 'enabled',
        exportedAt: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(config, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'api-config.json';
    a.click();
    URL.revokeObjectURL(url);
    
    showNotification('API configuration exported successfully', 'success');
}

function saveAllAPISettings() {
    // Collect all form data
    const settings = {
        // API Configuration
        apiVersion: document.getElementById('apiVersion').value,
        apiTimeout: document.getElementById('apiTimeout').value,
        corsEnabled: document.getElementById('corsEnabled').checked,
        httpsOnly: document.getElementById('httpsOnly').checked,
        allowedOrigins: document.getElementById('allowedOrigins').value,
        
        // Slack Integration
        slackEnabled: document.getElementById('slackEnabled').checked,
        slackWebhookUrl: document.getElementById('slackWebhookUrl').value,
        slackChannel: document.getElementById('slackChannel').value,
        
        // Email Integration
        emailEnabled: document.getElementById('emailEnabled').checked,
        smtpHost: document.getElementById('smtpHost').value,
        smtpPort: document.getElementById('smtpPort').value,
        smtpUsername: document.getElementById('smtpUsername').value,
        smtpPassword: document.getElementById('smtpPassword').value,
        smtpEncryption: document.getElementById('smtpEncryption').value,
        
        // SMS Integration
        smsEnabled: document.getElementById('smsEnabled').checked,
        twilioAccountSid: document.getElementById('twilioAccountSid').value,
        twilioAuthToken: document.getElementById('twilioAuthToken').value,
        twilioPhoneNumber: document.getElementById('twilioPhoneNumber').value,
        
        // Google Sheets Integration
        googleSheetsEnabled: document.getElementById('googleSheetsEnabled').checked,
        googleServiceAccount: document.getElementById('googleServiceAccount').value,
        googleSpreadsheetId: document.getElementById('googleSpreadsheetId').value,
        
        // Rate Limiting
        rateLimitingEnabled: document.getElementById('rateLimitingEnabled').checked,
        globalRateLimit: document.getElementById('globalRateLimit').value,
        perKeyRateLimit: document.getElementById('perKeyRateLimit').value,
        burstLimit: document.getElementById('burstLimit').value,
        rateLimitWindow: document.getElementById('rateLimitWindow').value,
        blockOnExceed: document.getElementById('blockOnExceed').checked,
        
        savedAt: new Date().toISOString()
    };
    
    // Save to localStorage
    localStorage.setItem('apiSettings', JSON.stringify(settings));
    
    showNotification('All API settings saved successfully!', 'success');
}

function refreshAPILogs() {
    showNotification('Refreshing API logs...', 'info');
    loadAPILogs();
}

function exportAPILogs() {
    showNotification('Exporting API logs...', 'info');
    
    // Simulate export
    setTimeout(() => {
        showNotification('API logs exported successfully', 'success');
    }, 2000);
}

function viewAPILogDetails(timestamp) {
    showNotification(`Viewing details for log entry: ${timestamp}`, 'info');
}

// Notification function
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAPISettings();
    initializeTooltips();
});

</script>
@endsection

// Load API keys
function loadAPIKeys() {
    const apiKeys = [
        {
            id: 1,
            name: 'Production API',
            key: 'pk_live_51H...****...3xY2',
            permissions: ['Read', 'Write'],
            status: 'Active',
            lastUsed: '2 hours ago'
        },
        {
            id: 2,
            name: 'Development API',
            key: 'pk_test_51H...****...9mN4',
            permissions: ['Read'],
            status: 'Active',
            lastUsed: '1 day ago'
        },
        {
            id: 3,
            name: 'Mobile App API',
            key: 'pk_live_51H...****...7kL8',
            permissions: ['Read', 'Write', 'Delete'],
            status: 'Inactive',
            lastUsed: '1 week ago'
        }
    ];

    const tableBody = document.getElementById('apiKeysTable');
    tableBody.innerHTML = '';

    apiKeys.forEach(key => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${key.name}</td>
            <td>
                <code class="text-muted">${key.key}</code>
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyAPIKey('${key.key}')">
                    <i class="fas fa-copy"></i>
                </button>
            </td>
            <td>
                ${key.permissions.map(p => `<span class="badge bg-secondary me-1">${p}</span>`).join('')}
            </td>
            <td>
                <span class="badge ${key.status === 'Active' ? 'bg-success' : 'bg-secondary'}">${key.status}</span>
            </td>
            <td>${key.lastUsed}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editAPIKey(${key.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteAPIKey(${key.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });

    // Populate test API key dropdown
    const testAPIKeySelect = document.getElementById('testAPIKey');
    if (testAPIKeySelect) {
        testAPIKeySelect.innerHTML = '<option value="">Select API Key</option>';
        apiKeys.forEach(key => {
            if (key.status === 'Active') {
                const option = document.createElement('option');
                option.value = key.key;
                option.textContent = key.name;
                testAPIKeySelect.appendChild(option);
            }
        });
    }
}

// Load webhooks
function loadWebhooks() {
    const webhooks = [
        {
    id: 1,
    name: 'Inventory Alerts',
    url: '{{ env('SLACK_WEBHOOK_URL', '') }}',
    events: ['inventory.created', 'stock.low'],
    status: 'Active',
    lastTriggered: '30 minutes ago'
},
        {
            id: 2,
            name: 'User Management',
            url: 'https://api.example.com/webhooks/users',
            events: ['user.created', 'user.updated'],
            status: 'Active',
            lastTriggered: '2 hours ago'
        },
        {
            id: 3,
            name: 'System Notifications',
            url: 'https://notifications.pnsdhampur.com/webhook',
            events: ['system.error', 'system.maintenance'],
            status: 'Inactive',
            lastTriggered: '1 week ago'
        }
    ];

    const tableBody = document.getElementById('webhooksTable');
    tableBody.innerHTML = '';

    webhooks.forEach(webhook => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${webhook.name}</td>
            <td>
                <code class="text-muted">${webhook.url.substring(0, 50)}...</code>
            </td>
            <td>
                ${webhook.events.map(e => `<span class="badge bg-info me-1">${e}</span>`).join('')}
            </td>
            <td>
                <span class="badge ${webhook.status === 'Active' ? 'bg-success' : 'bg-secondary'}">${webhook.status}</span>
            </td>
            <td>${webhook.lastTriggered}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="editWebhook(${webhook.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-warning" onclick="testWebhook(${webhook.id})">
                        <i class="fas fa-test-tube"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteWebhook(${webhook.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Load API statistics
function loadAPIStatistics() {
    document.getElementById('totalAPIKeys').textContent = '3';
    document.getElementById('activeAPIKeys').textContent = '2';
    document.getElementById('todayRequests').textContent = '1,247';
    document.getElementById('monthlyRequests').textContent = '45,892';
}

// Load recent API activity
function loadRecentAPIActivity() {
    const activities = [
        { type: 'success', message: 'API key "Production API" used', time: '2 min ago' },
        { type: 'warning', message: 'Rate limit reached for key "Dev API"', time: '15 min ago' },
        { type: 'info', message: 'New API key generated', time: '1 hour ago' },
        { type: 'success', message: 'Webhook delivered successfully', time: '2 hours ago' }
    ];

    const container = document.getElementById('recentAPIActivity');
    container.innerHTML = '';

    activities.forEach(activity => {
        const item = document.createElement('div');
        item.className = `activity-item ${activity.type}`;
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted">${activity.message}</small>
                </div>
                <small class="text-muted">${activity.time}</small>
            </div>
        `;
        container.appendChild(item);
    });
}

// Load webhook statistics
function loadWebhookStatistics() {
    document.getElementById('totalWebhooks').textContent = '3';
    document.getElementById('activeWebhooks').textContent = '2';
    document.getElementById('successfulDeliveries').textContent = '98.5%';
    document.getElementById('failedDeliveries').textContent = '1.5%';
}

// Load recent webhook deliveries
function loadRecentWebhookDeliveries() {
    const deliveries = [
        { webhook: 'Inventory Alerts', status: 'success', time: '30 min ago' },
        { webhook: 'User Management', status: 'success', time: '1 hour ago' },
        { webhook: 'System Notifications', status: 'failed', time: '2 hours ago' },
        { webhook: 'Inventory Alerts', status: 'success', time: '3 hours ago' }
    ];

    const container = document.getElementById('recentWebhookDeliveries');
    container.innerHTML = '';

    deliveries.forEach(delivery => {
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="fw-medium">${delivery.webhook}</small>
                    <br>
                    <span class="badge ${delivery.status === 'success' ? 'bg-success' : 'bg-danger'} badge-sm">
                        ${delivery.status}
                    </span>
                </div>
                <small class="text-muted">${delivery.time}</small>
            </div>
        `;
        container.appendChild(item);
    });
}

// Load integration status
function loadIntegrationStatus() {
    const integrations = [
        { name: 'Slack', status: 'connected', lastSync: '5 min ago' },
        { name: 'Email', status: 'connected', lastSync: '1 hour ago' },
        { name: 'SMS', status: 'disconnected', lastSync: 'Never' },
        { name: 'Google Sheets', status: 'error', lastSync: '2 days ago' }
    ];

    const container = document.getElementById('integrationStatus');
    container.innerHTML = '';

    integrations.forEach(integration => {
        const item = document.createElement('div');
        item.className = 'activity-item';
        
        let statusClass = 'secondary';
        let statusIcon = 'circle';
        
        switch(integration.status) {
            case 'connected':
                statusClass = 'success';
                statusIcon = 'check-circle';
                break;
            case 'disconnected':
                statusClass = 'secondary';
                statusIcon = 'times-circle';
                break;
            case 'error':
                statusClass = 'danger';
                statusIcon = 'exclamation-triangle';
                break;
        }
        
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${statusIcon} text-${statusClass} me-2"></i>
                        <span class="fw-medium">${integration.name}</span>
                    </div>
                    <small class="text-muted">Last sync: ${integration.lastSync}</small>
                </div>
                <span class="badge bg-${statusClass}">${integration.status}</span>
            </div>
        `;
        container.appendChild(item);
    });
}

// Load integration logs
function loadIntegrationLogs() {
    const logs = [
        { integration: 'Slack', action: 'Message sent', status: 'success', time: '5 min ago' },
        { integration: 'Email', action: 'Notification sent', status: 'success', time: '1 hour ago' },
        { integration: 'Google Sheets', action: 'Data sync failed', status: 'error', time: '2 hours ago' },
        { integration: 'SMS', action: 'Connection test', status: 'failed', time: '1 day ago' }
    ];

    const container = document.getElementById('integrationLogs');
    container.innerHTML = '';

    logs.forEach(log => {
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-medium">${log.integration}</div>
                    <small class="text-muted">${log.action}</small>
                    <br>
                    <span class="badge ${log.status === 'success' ? 'bg-success' : log.status === 'error' ? 'bg-danger' : 'bg-warning'} badge-sm">
                        ${log.status}
                    </span>
                </div>
                <small class="text-muted">${log.time}</small>
            </div>
        `;
        container.appendChild(item);
    });
}