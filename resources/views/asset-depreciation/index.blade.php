@extends('layouts.app')

@section('title', 'Asset Depreciation Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Asset Depreciation Management</h1>
                    <p class="text-muted">Track and manage asset depreciation with automated calculations</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" onclick="runCalculations()">
                        <i class="fas fa-calculator"></i> Run Calculations
                    </button>
                    <button class="btn btn-primary" onclick="showSetupModal()">
                        <i class="fas fa-plus"></i> Setup Depreciation
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')">Export PDF</a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportReport('excel')">Export Excel</a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportReport('csv')">Export CSV</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Assets</h6>
                            <h3 class="mb-0" id="total-assets">-</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-boxes fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Active Assets</h6>
                            <h3 class="mb-0" id="active-assets">-</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Overdue Calculations</h6>
                            <h3 class="mb-0" id="overdue-calculations">-</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Current Value</h6>
                            <h3 class="mb-0" id="total-current-value">-</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard -->
    <div class="row mb-4">
        <!-- Depreciation Trends Chart -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Depreciation Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="depreciationTrendsChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Method Distribution -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Depreciation Methods</h5>
                </div>
                <div class="card-body">
                    <canvas id="methodDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Assets Requiring Attention -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Assets Requiring Attention</h5>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="attentionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overdue-tab" data-bs-toggle="tab" data-bs-target="#overdue" type="button" role="tab">
                                Overdue Calculations <span class="badge bg-danger ms-1" id="overdue-count">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="nearly-depreciated-tab" data-bs-toggle="tab" data-bs-target="#nearly-depreciated" type="button" role="tab">
                                Nearly Depreciated <span class="badge bg-warning ms-1" id="nearly-depreciated-count">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="no-setup-tab" data-bs-toggle="tab" data-bs-target="#no-setup" type="button" role="tab">
                                No Depreciation Setup <span class="badge bg-info ms-1" id="no-setup-count">0</span>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content mt-3" id="attentionTabContent">
                        <div class="tab-pane fade show active" id="overdue" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm" id="overdue-table">
                                    <thead>
                                        <tr>
                                            <th>Asset Name</th>
                                            <th>Last Calculation</th>
                                            <th>Days Overdue</th>
                                            <th>Current Value</th>
                                            <th>Method</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="nearly-depreciated" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm" id="nearly-depreciated-table">
                                    <thead>
                                        <tr>
                                            <th>Asset Name</th>
                                            <th>Depreciation %</th>
                                            <th>Current Value</th>
                                            <th>Remaining Months</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="no-setup" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-sm" id="no-setup-table">
                                    <thead>
                                        <tr>
                                            <th>Asset Name</th>
                                            <th>Purchase Price</th>
                                            <th>Purchase Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Calculations -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Depreciation Calculations</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="recent-calculations-table">
                            <thead>
                                <tr>
                                    <th>Asset Name</th>
                                    <th>Calculation Date</th>
                                    <th>Depreciation Amount</th>
                                    <th>Book Value</th>
                                    <th>Method</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Setup Depreciation Modal -->
<div class="modal fade" id="setupDepreciationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Setup Asset Depreciation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="setupDepreciationForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="inventory_item_id" class="form-label">Asset</label>
                                <select class="form-select" id="inventory_item_id" name="inventory_item_id" required>
                                    <option value="">Select Asset</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="depreciation_method" class="form-label">Depreciation Method</label>
                                <select class="form-select" id="depreciation_method" name="depreciation_method" required>
                                    <option value="">Select Method</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="useful_life_years" class="form-label">Useful Life (Years)</label>
                                <input type="number" class="form-control" id="useful_life_years" name="useful_life_years" min="1" max="50" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="useful_life_months" class="form-label">Additional Months</label>
                                <input type="number" class="form-control" id="useful_life_months" name="useful_life_months" min="0" max="11" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="salvage_value" class="form-label">Salvage Value</label>
                                <input type="number" class="form-control" id="salvage_value" name="salvage_value" min="0" step="0.01" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="annual_depreciation_rate" class="form-label">Annual Rate (%)</label>
                                <input type="number" class="form-control" id="annual_depreciation_rate" name="annual_depreciation_rate" min="0" max="100" step="0.01">
                                <small class="form-text text-muted">For declining balance methods</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="depreciation_start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="depreciation_start_date" name="depreciation_start_date">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Setup Depreciation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualEntryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manual Depreciation Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="manualEntryForm">
                <div class="modal-body">
                    <input type="hidden" id="manual_asset_id" name="asset_id">
                    <div class="mb-3">
                        <label for="calculation_date" class="form-label">Calculation Date</label>
                        <input type="date" class="form-control" id="calculation_date" name="calculation_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="depreciation_amount" class="form-label">Depreciation Amount</label>
                        <input type="number" class="form-control" id="depreciation_amount" name="depreciation_amount" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_reason" class="form-label">Adjustment Reason</label>
                        <textarea class="form-control" id="adjustment_reason" name="adjustment_reason" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.nav-tabs .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75em;
}

.opacity-75 {
    opacity: 0.75;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
}

.chart-container {
    position: relative;
    height: 300px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let depreciationTrendsChart;
let methodDistributionChart;

$(document).ready(function() {
    loadDashboardData();
    loadAvailableAssets();
    loadDepreciationMethods();
    
    // Setup form handlers
    $('#setupDepreciationForm').on('submit', handleSetupSubmit);
    $('#manualEntryForm').on('submit', handleManualEntrySubmit);
    
    // Auto-refresh every 5 minutes
    setInterval(loadDashboardData, 300000);
});

function loadDashboardData() {
    $.ajax({
        url: '/api/asset-depreciation/dashboard',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateSummaryCards(response.data.summary);
                updateDepreciationTrends(response.data.depreciation_trends);
                updateMethodDistribution(response.data.method_distribution);
                updateRecentCalculations(response.data.recent_calculations);
                loadAttentionItems();
            }
        },
        error: function(xhr) {
            console.error('Failed to load dashboard data:', xhr.responseJSON);
            showAlert('Failed to load dashboard data', 'danger');
        }
    });
}

function updateSummaryCards(summary) {
    $('#total-assets').text(summary.total_assets || 0);
    $('#active-assets').text(summary.active_assets || 0);
    $('#overdue-calculations').text(summary.overdue_calculations || 0);
    $('#total-current-value').text('₹' + (summary.total_current_value || 0).toLocaleString());
}

function updateDepreciationTrends(trends) {
    const ctx = document.getElementById('depreciationTrendsChart').getContext('2d');
    
    if (depreciationTrendsChart) {
        depreciationTrendsChart.destroy();
    }
    
    depreciationTrendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trends.labels || [],
            datasets: [{
                label: 'Monthly Depreciation',
                data: trends.depreciation_amounts || [],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4
            }, {
                label: 'Calculation Count',
                data: trends.calculation_counts || [],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                yAxisID: 'y1',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

function updateMethodDistribution(distribution) {
    const ctx = document.getElementById('methodDistributionChart').getContext('2d');
    
    if (methodDistributionChart) {
        methodDistributionChart.destroy();
    }
    
    methodDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: distribution.labels || [],
            datasets: [{
                data: distribution.data || [],
                backgroundColor: distribution.colors || ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function updateRecentCalculations(calculations) {
    const tbody = $('#recent-calculations-table tbody');
    tbody.empty();
    
    calculations.forEach(function(calc) {
        const row = `
            <tr>
                <td>${calc.asset_name}</td>
                <td>${calc.calculation_date}</td>
                <td>₹${calc.depreciation_amount.toLocaleString()}</td>
                <td>₹${calc.book_value.toLocaleString()}</td>
                <td>${calc.method}</td>
                <td><span class="badge ${calc.entry_type_badge}">${calc.entry_type}</span></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="viewAssetHistory(${calc.id})">
                        <i class="fas fa-history"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function loadAttentionItems() {
    $.ajax({
        url: '/api/asset-depreciation/requires-attention',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateOverdueAssets(response.data.overdue_calculations);
                updateNearlyDepreciated(response.data.nearly_depreciated);
                updateNoSetupAssets(response.data.missing_depreciation_setup);
            }
        },
        error: function(xhr) {
            console.error('Failed to load attention items:', xhr.responseJSON);
        }
    });
}

function updateOverdueAssets(assets) {
    const tbody = $('#overdue-table tbody');
    tbody.empty();
    $('#overdue-count').text(assets.length);
    
    assets.forEach(function(asset) {
        const row = `
            <tr>
                <td>${asset.asset_name}</td>
                <td>${asset.last_calculation}</td>
                <td><span class="badge bg-danger">${asset.days_overdue} days</span></td>
                <td>₹${asset.current_book_value.toLocaleString()}</td>
                <td>${asset.depreciation_method}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="calculateAsset(${asset.id})">
                        Calculate
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function updateNearlyDepreciated(assets) {
    const tbody = $('#nearly-depreciated-table tbody');
    tbody.empty();
    $('#nearly-depreciated-count').text(assets.length);
    
    assets.forEach(function(asset) {
        const row = `
            <tr>
                <td>${asset.asset_name}</td>
                <td><span class="badge bg-warning">${asset.depreciation_percentage}%</span></td>
                <td>₹${asset.current_book_value.toLocaleString()}</td>
                <td>${asset.remaining_months} months</td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="viewAssetDetails(${asset.id})">
                        View Details
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function updateNoSetupAssets(assets) {
    const tbody = $('#no-setup-table tbody');
    tbody.empty();
    $('#no-setup-count').text(assets.length);
    
    assets.forEach(function(asset) {
        const row = `
            <tr>
                <td>${asset.name}</td>
                <td>₹${asset.purchase_price.toLocaleString()}</td>
                <td>${asset.purchase_date || 'N/A'}</td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="setupDepreciationForAsset(${asset.id})">
                        Setup
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function loadAvailableAssets() {
    $.ajax({
        url: '/api/asset-depreciation/available-assets',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#inventory_item_id');
                select.empty().append('<option value="">Select Asset</option>');
                
                response.data.forEach(function(asset) {
                    select.append(`<option value="${asset.id}">${asset.name} - ₹${asset.purchase_price}</option>`);
                });
            }
        }
    });
}

function loadDepreciationMethods() {
    $.ajax({
        url: '/api/asset-depreciation/methods',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#depreciation_method');
                select.empty().append('<option value="">Select Method</option>');
                
                Object.entries(response.data).forEach(function([key, value]) {
                    select.append(`<option value="${key}">${value}</option>`);
                });
            }
        }
    });
}

function runCalculations() {
    if (!confirm('Run automated depreciation calculations for all overdue assets?')) {
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calculating...';
    btn.disabled = true;
    
    $.ajax({
        url: '/api/asset-depreciation/run-calculations',
        method: 'POST',
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadDashboardData();
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function(xhr) {
            showAlert('Failed to run calculations', 'danger');
        },
        complete: function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

function calculateAsset(assetId) {
    $.ajax({
        url: `/api/asset-depreciation/${assetId}/calculate`,
        method: 'POST',
        success: function(response) {
            if (response.success) {
                showAlert('Depreciation calculated successfully', 'success');
                loadDashboardData();
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function(xhr) {
            showAlert('Failed to calculate depreciation', 'danger');
        }
    });
}

function showSetupModal() {
    $('#setupDepreciationModal').modal('show');
}

function setupDepreciationForAsset(assetId) {
    $('#inventory_item_id').val(assetId);
    showSetupModal();
}

function handleSetupSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    $.ajax({
        url: '/api/asset-depreciation',
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                showAlert('Depreciation setup completed successfully', 'success');
                $('#setupDepreciationModal').modal('hide');
                e.target.reset();
                loadDashboardData();
                loadAvailableAssets();
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                Object.values(errors).forEach(function(errorArray) {
                    showAlert(errorArray[0], 'danger');
                });
            } else {
                showAlert('Failed to setup depreciation', 'danger');
            }
        }
    });
}

function showManualEntryModal(assetId) {
    $('#manual_asset_id').val(assetId);
    $('#calculation_date').val(new Date().toISOString().split('T')[0]);
    $('#manualEntryModal').modal('show');
}

function handleManualEntrySubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    const assetId = data.asset_id;
    
    $.ajax({
        url: `/api/asset-depreciation/${assetId}/manual-entry`,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                showAlert('Manual entry created successfully', 'success');
                $('#manualEntryModal').modal('hide');
                e.target.reset();
                loadDashboardData();
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                Object.values(errors).forEach(function(errorArray) {
                    showAlert(errorArray[0], 'danger');
                });
            } else {
                showAlert('Failed to create manual entry', 'danger');
            }
        }
    });
}

function exportReport(format) {
    const params = new URLSearchParams({
        format: format
    });
    
    $.ajax({
        url: `/api/asset-depreciation/export?${params}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                if (response.download_url && response.download_url !== '#') {
                    window.open(response.download_url, '_blank');
                }
            } else {
                showAlert(response.message, 'danger');
            }
        },
        error: function(xhr) {
            showAlert('Failed to export report', 'danger');
        }
    });
}

function viewAssetDetails(assetId) {
    // Implementation for viewing asset details
    window.location.href = `/asset-depreciation/${assetId}`;
}

function viewAssetHistory(assetId) {
    // Implementation for viewing asset history
    window.location.href = `/asset-depreciation/${assetId}/history`;
}

function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}
</script>
@endsection