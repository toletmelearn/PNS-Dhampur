@extends('layouts.app')

@section('title', 'Inventory Settings')

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
                            <li class="breadcrumb-item active">Inventory Configuration</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-0">Inventory Configuration</h1>
                    <p class="text-muted">Configure inventory-specific settings, thresholds, and policies</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary me-2" onclick="resetToDefaults()">
                        <i class="fas fa-undo"></i> Reset to Defaults
                    </button>
                    <button type="button" class="btn btn-success" onclick="saveAllSettings()">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="inventorySettingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                        <i class="fas fa-cog"></i> General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                        <i class="fas fa-tags"></i> Categories
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="locations-tab" data-bs-toggle="tab" data-bs-target="#locations" type="button" role="tab">
                        <i class="fas fa-map-marker-alt"></i> Locations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                        <i class="fas fa-tools"></i> Maintenance
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                        <i class="fas fa-chart-bar"></i> Reports
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab">
                        <i class="fas fa-cogs"></i> Advanced
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="inventorySettingsTabContent">
        <!-- General Settings Tab -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Stock Management -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-boxes text-primary"></i> Stock Management
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="stockManagementForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="lowStockThreshold" class="form-label">Low Stock Threshold</label>
                                            <input type="number" class="form-control" id="lowStockThreshold" min="0" value="10">
                                            <div class="form-text">Alert when stock falls below this number</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="criticalStockThreshold" class="form-label">Critical Stock Threshold</label>
                                            <input type="number" class="form-control" id="criticalStockThreshold" min="0" value="5">
                                            <div class="form-text">Critical alert threshold</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="reorderPoint" class="form-label">Auto Reorder Point</label>
                                            <input type="number" class="form-control" id="reorderPoint" min="0" value="15">
                                            <div class="form-text">Automatically suggest reorders</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maxStockLevel" class="form-label">Maximum Stock Level</label>
                                            <input type="number" class="form-control" id="maxStockLevel" min="0" value="1000">
                                            <div class="form-text">Maximum allowed stock quantity</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableAutoReorder">
                                    <label class="form-check-label" for="enableAutoReorder">
                                        Enable Automatic Reorder Suggestions
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="trackSerialNumbers" checked>
                                    <label class="form-check-label" for="trackSerialNumbers">
                                        Track Serial Numbers for Items
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Item Configuration -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cube text-info"></i> Item Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="itemConfigForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="itemIdPrefix" class="form-label">Item ID Prefix</label>
                                            <input type="text" class="form-control" id="itemIdPrefix" value="ITM" maxlength="5">
                                            <div class="form-text">Prefix for auto-generated item IDs</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="itemIdLength" class="form-label">Item ID Length</label>
                                            <select class="form-select" id="itemIdLength">
                                                <option value="6">6 digits</option>
                                                <option value="8" selected>8 digits</option>
                                                <option value="10">10 digits</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="defaultCategory" class="form-label">Default Category</label>
                                            <select class="form-select" id="defaultCategory">
                                                <option value="">Select default category...</option>
                                                <!-- Categories will be loaded dynamically -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="defaultLocation" class="form-label">Default Location</label>
                                            <select class="form-select" id="defaultLocation">
                                                <option value="">Select default location...</option>
                                                <!-- Locations will be loaded dynamically -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="autoGenerateItemId" checked>
                                    <label class="form-check-label" for="autoGenerateItemId">
                                        Auto-generate Item IDs
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requireItemImages">
                                    <label class="form-check-label" for="requireItemImages">
                                        Require Images for New Items
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableBarcodeScanning" checked>
                                    <label class="form-check-label" for="enableBarcodeScanning">
                                        Enable Barcode Scanning
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Quick Stats -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Inventory Overview</h6>
                        </div>
                        <div class="card-body">
                            <div id="inventoryStats" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Recent Configuration Changes</h6>
                        </div>
                        <div class="card-body">
                            <div id="recentActivity" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Tab -->
        <div class="tab-pane fade" id="categories" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tags text-primary"></i> Category Management
                            </h5>
                            <button type="button" class="btn btn-primary btn-sm" onclick="addNewCategory()">
                                <i class="fas fa-plus"></i> Add Category
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="categoriesTable" class="table-responsive">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading categories...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Category Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div id="categoryStats" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Locations Tab -->
        <div class="tab-pane fade" id="locations" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-map-marker-alt text-success"></i> Location Management
                            </h5>
                            <button type="button" class="btn btn-success btn-sm" onclick="addNewLocation()">
                                <i class="fas fa-plus"></i> Add Location
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="locationsTable" class="table-responsive">
                                <div class="text-center">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading locations...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Location Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div id="locationStats" class="text-center">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Tab -->
        <div class="tab-pane fade" id="maintenance" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tools text-warning"></i> Maintenance Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="maintenanceConfigForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="defaultMaintenanceInterval" class="form-label">Default Maintenance Interval (days)</label>
                                            <input type="number" class="form-control" id="defaultMaintenanceInterval" min="1" value="90">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maintenanceReminderDays" class="form-label">Reminder Days Before Due</label>
                                            <input type="number" class="form-control" id="maintenanceReminderDays" min="1" value="7">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="overdueGracePeriod" class="form-label">Overdue Grace Period (days)</label>
                                            <input type="number" class="form-control" id="overdueGracePeriod" min="0" value="3">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maintenanceEmailTemplate" class="form-label">Email Template</label>
                                            <select class="form-select" id="maintenanceEmailTemplate">
                                                <option value="default">Default Template</option>
                                                <option value="detailed">Detailed Template</option>
                                                <option value="simple">Simple Template</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="autoScheduleMaintenance" checked>
                                    <label class="form-check-label" for="autoScheduleMaintenance">
                                        Auto-schedule Maintenance Tasks
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="sendMaintenanceReminders" checked>
                                    <label class="form-check-label" for="sendMaintenanceReminders">
                                        Send Email Reminders
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="requireMaintenancePhotos">
                                    <label class="form-check-label" for="requireMaintenancePhotos">
                                        Require Photos for Maintenance Records
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Maintenance Overview</h6>
                        </div>
                        <div class="card-body">
                            <div id="maintenanceStats" class="text-center">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div class="tab-pane fade" id="reports" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar text-info"></i> Report Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="reportConfigForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="defaultReportFormat" class="form-label">Default Report Format</label>
                                            <select class="form-select" id="defaultReportFormat">
                                                <option value="pdf">PDF</option>
                                                <option value="excel" selected>Excel</option>
                                                <option value="csv">CSV</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="autoReportFrequency" class="form-label">Auto Report Frequency</label>
                                            <select class="form-select" id="autoReportFrequency">
                                                <option value="none">Disabled</option>
                                                <option value="daily">Daily</option>
                                                <option value="weekly" selected>Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="reportRetentionDays" class="form-label">Report Retention (days)</label>
                                            <input type="number" class="form-control" id="reportRetentionDays" min="1" value="365">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maxReportSize" class="form-label">Max Report Size (MB)</label>
                                            <input type="number" class="form-control" id="maxReportSize" min="1" value="50">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="includeImagesInReports">
                                    <label class="form-check-label" for="includeImagesInReports">
                                        Include Images in Reports
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableReportScheduling" checked>
                                    <label class="form-check-label" for="enableReportScheduling">
                                        Enable Report Scheduling
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="compressReports">
                                    <label class="form-check-label" for="compressReports">
                                        Compress Large Reports
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Report Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div id="reportStats" class="text-center">
                                <div class="spinner-border text-info" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Tab -->
        <div class="tab-pane fade" id="advanced" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cogs text-danger"></i> Advanced Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Warning:</strong> These settings can significantly impact system performance. Change with caution.
                            </div>
                            <form id="advancedConfigForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="batchProcessingSize" class="form-label">Batch Processing Size</label>
                                            <input type="number" class="form-control" id="batchProcessingSize" min="10" max="1000" value="100">
                                            <div class="form-text">Number of items to process in each batch</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cacheTimeout" class="form-label">Cache Timeout (minutes)</label>
                                            <input type="number" class="form-control" id="cacheTimeout" min="1" value="60">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="maxImageSize" class="form-label">Max Image Size (MB)</label>
                                            <input type="number" class="form-control" id="maxImageSize" min="1" max="50" value="5">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="imageQuality" class="form-label">Image Compression Quality</label>
                                            <select class="form-select" id="imageQuality">
                                                <option value="high">High (90%)</option>
                                                <option value="medium" selected>Medium (75%)</option>
                                                <option value="low">Low (60%)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableDebugMode">
                                    <label class="form-check-label" for="enableDebugMode">
                                        Enable Debug Mode
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableApiLogging" checked>
                                    <label class="form-check-label" for="enableApiLogging">
                                        Enable API Request Logging
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enablePerformanceMonitoring">
                                    <label class="form-check-label" for="enablePerformanceMonitoring">
                                        Enable Performance Monitoring
                                    </label>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- System Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tools text-secondary"></i> System Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="clearCache()">
                                        <i class="fas fa-broom"></i> Clear System Cache
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-info w-100 mb-2" onclick="rebuildIndex()">
                                        <i class="fas fa-sync"></i> Rebuild Search Index
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="optimizeDatabase()">
                                        <i class="fas fa-database"></i> Optimize Database
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-success w-100 mb-2" onclick="generateSystemReport()">
                                        <i class="fas fa-file-alt"></i> Generate System Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">System Performance</h6>
                        </div>
                        <div class="card-body">
                            <div id="systemPerformance" class="text-center">
                                <div class="spinner-border text-secondary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalTitle">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="categoryId">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="categoryDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="categoryColor" class="form-label">Color</label>
                        <input type="color" class="form-control form-control-color" id="categoryColor" value="#007bff">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="categoryActive" checked>
                        <label class="form-check-label" for="categoryActive">
                            Active
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">Save Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Location Modal -->
<div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalTitle">Add New Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="locationForm">
                    <input type="hidden" id="locationId">
                    <div class="mb-3">
                        <label for="locationName" class="form-label">Location Name</label>
                        <input type="text" class="form-control" id="locationName" required>
                    </div>
                    <div class="mb-3">
                        <label for="locationCode" class="form-label">Location Code</label>
                        <input type="text" class="form-control" id="locationCode" maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label for="locationDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="locationDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="locationCapacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="locationCapacity" min="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="locationActive" checked>
                        <label class="form-check-label" for="locationActive">
                            Active
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="saveLocation()">Save Location</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the page
    loadInitialData();
    
    // Tab change handlers
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            loadTabData(target);
        });
    });
});

function loadInitialData() {
    loadInventoryStats();
    loadRecentActivity();
    loadCategories();
    loadLocations();
    loadSettings();
}

function loadInventoryStats() {
    // Simulate API call
    setTimeout(() => {
        const statsHtml = `
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h4 class="text-primary mb-0">1,247</h4>
                        <small class="text-muted">Total Items</small>
                    </div>
                </div>
                <div class="col-6">
                    <h4 class="text-warning mb-0">23</h4>
                    <small class="text-muted">Low Stock</small>
                </div>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h4 class="text-success mb-0">15</h4>
                        <small class="text-muted">Categories</small>
                    </div>
                </div>
                <div class="col-6">
                    <h4 class="text-info mb-0">8</h4>
                    <small class="text-muted">Locations</small>
                </div>
            </div>
        `;
        document.getElementById('inventoryStats').innerHTML = statsHtml;
    }, 1000);
}

function loadRecentActivity() {
    // Simulate API call
    setTimeout(() => {
        const activityHtml = `
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0 py-2">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-cog text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <div class="fw-bold small">Stock threshold updated</div>
                            <div class="text-muted small">2 hours ago</div>
                        </div>
                    </div>
                </div>
                <div class="list-group-item border-0 px-0 py-2">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tags text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <div class="fw-bold small">New category added</div>
                            <div class="text-muted small">1 day ago</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('recentActivity').innerHTML = activityHtml;
    }, 1200);
}

function loadCategories() {
    // Simulate API call
    setTimeout(() => {
        const categoriesHtml = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                Electronics
                            </div>
                        </td>
                        <td>245</td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editCategory(1)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(1)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-success rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                Furniture
                            </div>
                        </td>
                        <td>89</td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editCategory(2)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(2)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        `;
        document.getElementById('categoriesTable').innerHTML = categoriesHtml;
        
        // Load category stats
        const categoryStatsHtml = `
            <div class="text-center">
                <h4 class="text-primary mb-0">15</h4>
                <small class="text-muted">Total Categories</small>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h5 class="text-success mb-0">14</h5>
                        <small class="text-muted">Active</small>
                    </div>
                </div>
                <div class="col-6">
                    <h5 class="text-warning mb-0">1</h5>
                    <small class="text-muted">Inactive</small>
                </div>
            </div>
        `;
        document.getElementById('categoryStats').innerHTML = categoryStatsHtml;
    }, 800);
}

function loadLocations() {
    // Simulate API call
    setTimeout(() => {
        const locationsHtml = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Capacity</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Main Warehouse</td>
                        <td>MW-001</td>
                        <td>1000</td>
                        <td>856</td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editLocation(1)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteLocation(1)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>Storage Room A</td>
                        <td>SRA-001</td>
                        <td>200</td>
                        <td>145</td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editLocation(2)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteLocation(2)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        `;
        document.getElementById('locationsTable').innerHTML = locationsHtml;
        
        // Load location stats
        const locationStatsHtml = `
            <div class="text-center">
                <h4 class="text-success mb-0">8</h4>
                <small class="text-muted">Total Locations</small>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h5 class="text-info mb-0">85%</h5>
                        <small class="text-muted">Avg. Utilization</small>
                    </div>
                </div>
                <div class="col-6">
                    <h5 class="text-primary mb-0">1,200</h5>
                    <small class="text-muted">Total Capacity</small>
                </div>
            </div>
        `;
        document.getElementById('locationStats').innerHTML = locationStatsHtml;
    }, 900);
}

function loadSettings() {
    // Load default categories and locations for dropdowns
    setTimeout(() => {
        const categories = [
            { id: 1, name: 'Electronics' },
            { id: 2, name: 'Furniture' },
            { id: 3, name: 'Office Supplies' }
        ];
        
        const locations = [
            { id: 1, name: 'Main Warehouse' },
            { id: 2, name: 'Storage Room A' },
            { id: 3, name: 'Storage Room B' }
        ];
        
        const categorySelect = document.getElementById('defaultCategory');
        const locationSelect = document.getElementById('defaultLocation');
        
        categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;
            categorySelect.appendChild(option);
        });
        
        locations.forEach(loc => {
            const option = document.createElement('option');
            option.value = loc.id;
            option.textContent = loc.name;
            locationSelect.appendChild(option);
        });
    }, 500);
}

function loadTabData(target) {
    switch(target) {
        case '#maintenance':
            loadMaintenanceStats();
            break;
        case '#reports':
            loadReportStats();
            break;
        case '#advanced':
            loadSystemPerformance();
            break;
    }
}

function loadMaintenanceStats() {
    setTimeout(() => {
        const statsHtml = `
            <div class="text-center">
                <h4 class="text-warning mb-0">45</h4>
                <small class="text-muted">Scheduled Tasks</small>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h5 class="text-success mb-0">12</h5>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
                <div class="col-6">
                    <h5 class="text-danger mb-0">3</h5>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
        `;
        document.getElementById('maintenanceStats').innerHTML = statsHtml;
    }, 600);
}

function loadReportStats() {
    setTimeout(() => {
        const statsHtml = `
            <div class="text-center">
                <h4 class="text-info mb-0">156</h4>
                <small class="text-muted">Generated Reports</small>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div class="border-end">
                        <h5 class="text-primary mb-0">24</h5>
                        <small class="text-muted">This Month</small>
                    </div>
                </div>
                <div class="col-6">
                    <h5 class="text-success mb-0">2.3GB</h5>
                    <small class="text-muted">Total Size</small>
                </div>
            </div>
        `;
        document.getElementById('reportStats').innerHTML = statsHtml;
    }, 600);
}

function loadSystemPerformance() {
    setTimeout(() => {
        const performanceHtml = `
            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <small>CPU Usage</small>
                    <small>45%</small>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: 45%"></div>
                </div>
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <small>Memory Usage</small>
                    <small>68%</small>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-warning" style="width: 68%"></div>
                </div>
            </div>
            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <small>Disk Usage</small>
                    <small>32%</small>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-info" style="width: 32%"></div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <small class="text-muted">Last updated: 2 minutes ago</small>
            </div>
        `;
        document.getElementById('systemPerformance').innerHTML = performanceHtml;
    }, 600);
}

// Category Management Functions
function addNewCategory() {
    document.getElementById('categoryModalTitle').textContent = 'Add New Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

function editCategory(id) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    document.getElementById('categoryId').value = id;
    // Load category data and populate form
    // This would typically be an API call
    new bootstrap.Modal(document.getElementById('categoryModal')).show();
}

function saveCategory() {
    const form = document.getElementById('categoryForm');
    if (form.checkValidity()) {
        // Save category via API
        showToast('Category saved successfully!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
        loadCategories();
    } else {
        form.reportValidity();
    }
}

function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category?')) {
        // Delete category via API
        showToast('Category deleted successfully!', 'success');
        loadCategories();
    }
}

// Location Management Functions
function addNewLocation() {
    document.getElementById('locationModalTitle').textContent = 'Add New Location';
    document.getElementById('locationForm').reset();
    document.getElementById('locationId').value = '';
    new bootstrap.Modal(document.getElementById('locationModal')).show();
}

function editLocation(id) {
    document.getElementById('locationModalTitle').textContent = 'Edit Location';
    document.getElementById('locationId').value = id;
    // Load location data and populate form
    new bootstrap.Modal(document.getElementById('locationModal')).show();
}

function saveLocation() {
    const form = document.getElementById('locationForm');
    if (form.checkValidity()) {
        // Save location via API
        showToast('Location saved successfully!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('locationModal')).hide();
        loadLocations();
    } else {
        form.reportValidity();
    }
}

function deleteLocation(id) {
    if (confirm('Are you sure you want to delete this location?')) {
        // Delete location via API
        showToast('Location deleted successfully!', 'success');
        loadLocations();
    }
}

// System Action Functions
function clearCache() {
    if (confirm('Are you sure you want to clear the system cache?')) {
        showToast('System cache cleared successfully!', 'success');
    }
}

function rebuildIndex() {
    if (confirm('Are you sure you want to rebuild the search index? This may take several minutes.')) {
        showToast('Search index rebuild started. You will be notified when complete.', 'info');
    }
}

function optimizeDatabase() {
    if (confirm('Are you sure you want to optimize the database? This may take several minutes.')) {
        showToast('Database optimization started. You will be notified when complete.', 'info');
    }
}

function generateSystemReport() {
    showToast('System report generation started. You will receive an email when ready.', 'info');
}

// Main Functions
function saveAllSettings() {
    // Collect all form data and save via API
    showToast('All settings saved successfully!', 'success');
}

function resetToDefaults() {
    if (confirm('Are you sure you want to reset all settings to their default values?')) {
        // Reset all forms to default values
        showToast('Settings reset to defaults!', 'info');
    }
}

// Utility Functions
function showToast(message, type = 'info') {
    // Create and show toast notification
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}
</script>

<style>
.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #6c757d;
}

.nav-tabs .nav-link.active {
    border-bottom-color: #007bff;
    color: #007bff;
    background: none;
}

.nav-tabs .nav-link:hover {
    border-bottom-color: #dee2e6;
    color: #495057;
}

.progress {
    background-color: #e9ecef;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.form-control:focus,
.form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn-outline-primary:hover,
.btn-outline-success:hover,
.btn-outline-info:hover,
.btn-outline-warning:hover,
.btn-outline-danger:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.list-group-item {
    transition: background-color 0.15s ease-in-out;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75em;
}

.spinner-border {
    width: 2rem;
    height: 2rem;
}
</style>
@endsection