@extends('layouts.app')

@section('title', 'Administrative Tools')

@section('content')
<style>
    .admin-card {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        border-radius: 15px;
        padding: 20px;
        color: white;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .admin-card:hover {
        transform: translateY(-5px);
    }
    
    .tool-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        margin-bottom: 20px;
        border-top: 4px solid;
        height: 100%;
    }
    
    .tool-card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        transform: translateY(-3px);
    }
    
    .tool-card.inventory { border-top-color: #3498db; }
    .tool-card.documents { border-top-color: #e74c3c; }
    .tool-card.settings { border-top-color: #2ecc71; }
    .tool-card.backup { border-top-color: #f39c12; }
    .tool-card.users { border-top-color: #9b59b6; }
    .tool-card.security { border-top-color: #1abc9c; }
    
    .tool-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.8;
    }
    
    .tool-card.inventory .tool-icon { color: #3498db; }
    .tool-card.documents .tool-icon { color: #e74c3c; }
    .tool-card.settings .tool-icon { color: #2ecc71; }
    .tool-card.backup .tool-icon { color: #f39c12; }
    .tool-card.users .tool-icon { color: #9b59b6; }
    .tool-card.security .tool-icon { color: #1abc9c; }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-item {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        text-align: center;
        border-left: 4px solid;
    }
    
    .stat-item.total { border-left-color: #3498db; }
    .stat-item.active { border-left-color: #2ecc71; }
    .stat-item.pending { border-left-color: #f39c12; }
    .stat-item.critical { border-left-color: #e74c3c; }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .inventory-item {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid #3498db;
        transition: all 0.3s ease;
    }
    
    .inventory-item:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        transform: translateX(5px);
    }
    
    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .status-available { background: #d4edda; color: #155724; }
    .status-low { background: #fff3cd; color: #856404; }
    .status-out { background: #f8d7da; color: #721c24; }
    .status-maintenance { background: #d1ecf1; color: #0c5460; }
    
    .document-item {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid #e74c3c;
        transition: all 0.3s ease;
    }
    
    .document-item:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        transform: translateX(5px);
    }
    
    .verification-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .verified { background: #d4edda; color: #155724; }
    .pending { background: #fff3cd; color: #856404; }
    .rejected { background: #f8d7da; color: #721c24; }
    .expired { background: #d1ecf1; color: #0c5460; }
    
    .action-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 25px;
        font-weight: bold;
        transition: all 0.3s ease;
        margin: 5px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        color: white;
    }
    
    .action-btn.danger {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
    }
    
    .action-btn.danger:hover {
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.6);
    }
    
    .action-btn.success {
        background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
    }
    
    .action-btn.success:hover {
        box-shadow: 0 6px 20px rgba(46, 204, 113, 0.6);
    }
    
    .settings-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .settings-toggle {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }
    
    .settings-toggle:last-child {
        border-bottom: none;
    }
    
    .toggle-switch {
        position: relative;
        width: 60px;
        height: 30px;
        background: #ccc;
        border-radius: 15px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    
    .toggle-switch.active {
        background: #2ecc71;
    }
    
    .toggle-switch::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 24px;
        height: 24px;
        background: white;
        border-radius: 50%;
        transition: transform 0.3s ease;
    }
    
    .toggle-switch.active::after {
        transform: translateX(30px);
    }
    
    .modal-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 10px 30px;
        border-radius: 25px;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .activity-log {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        max-height: 400px;
        overflow-y: auto;
    }
    
    .activity-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.2rem;
    }
    
    .activity-icon.info { background: #e3f2fd; color: #1976d2; }
    .activity-icon.success { background: #e8f5e8; color: #2e7d32; }
    .activity-icon.warning { background: #fff8e1; color: #f57c00; }
    .activity-icon.error { background: #ffebee; color: #d32f2f; }
</style>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="admin-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2><i class="fas fa-cogs me-3"></i>Administrative Tools</h2>
                <p class="mb-0">Manage system settings, inventory, documents, and administrative functions</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-light btn-lg" onclick="generateSystemReport()">
                    <i class="fas fa-chart-bar me-2"></i>System Report
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-item total">
            <div class="stat-number" id="totalItems">156</div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-item active">
            <div class="stat-number" id="activeUsers">42</div>
            <div class="stat-label">Active Users</div>
        </div>
        <div class="stat-item pending">
            <div class="stat-number" id="pendingTasks">8</div>
            <div class="stat-label">Pending Tasks</div>
        </div>
        <div class="stat-item critical">
            <div class="stat-number" id="criticalAlerts">3</div>
            <div class="stat-label">Critical Alerts</div>
        </div>
    </div>

    <!-- Administrative Tools Grid -->
    <div class="row">
        <div class="col-md-4">
            <div class="tool-card inventory">
                <div class="text-center">
                    <div class="tool-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h4>Inventory Management</h4>
                    <p class="text-muted">Manage school inventory, equipment, and supplies</p>
                    <button class="action-btn" onclick="openInventoryManager()">
                        <i class="fas fa-box-open me-2"></i>Manage Inventory
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="tool-card documents">
                <div class="text-center">
                    <div class="tool-icon">
                        <i class="fas fa-file-check"></i>
                    </div>
                    <h4>Document Verification</h4>
                    <p class="text-muted">Verify and manage student and staff documents</p>
                    <button class="action-btn" onclick="openDocumentVerification()">
                        <i class="fas fa-check-circle me-2"></i>Verify Documents
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="tool-card settings">
                <div class="text-center">
                    <div class="tool-icon">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <h4>System Settings</h4>
                    <p class="text-muted">Configure system preferences and parameters</p>
                    <button class="action-btn" onclick="openSystemSettings()">
                        <i class="fas fa-cog me-2"></i>System Settings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="tool-card backup">
                <div class="text-center">
                    <div class="tool-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h4>Backup & Restore</h4>
                    <p class="text-muted">Manage system backups and data recovery</p>
                    <button class="action-btn" onclick="openBackupManager()">
                        <i class="fas fa-download me-2"></i>Backup Manager
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="tool-card users">
                <div class="text-center">
                    <div class="tool-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h4>User Management</h4>
                    <p class="text-muted">Manage user accounts, roles, and permissions</p>
                    <button class="action-btn" onclick="openUserManager()">
                        <i class="fas fa-user-edit me-2"></i>Manage Users
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="tool-card security">
                <div class="text-center">
                    <div class="tool-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Security Center</h4>
                    <p class="text-muted">Monitor security logs and system access</p>
                    <button class="action-btn" onclick="openSecurityCenter()">
                        <i class="fas fa-lock me-2"></i>Security Center
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity and Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-history me-2"></i>Recent Administrative Activity</h5>
                </div>
                <div class="card-body">
                    <div class="activity-log">
                        <div class="activity-item">
                            <div class="activity-icon success">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <strong>System Backup Completed</strong><br>
                                <small class="text-muted">Daily backup completed successfully - 2 hours ago</small>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon info">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <strong>New User Account Created</strong><br>
                                <small class="text-muted">Teacher account for John Smith created - 4 hours ago</small>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon warning">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <strong>Low Inventory Alert</strong><br>
                                <small class="text-muted">Stationery supplies running low - 6 hours ago</small>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon success">
                                <i class="fas fa-file-check"></i>
                            </div>
                            <div>
                                <strong>Document Verification</strong><br>
                                <small class="text-muted">15 student documents verified - 8 hours ago</small>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon error">
                                <i class="fas fa-times"></i>
                            </div>
                            <div>
                                <strong>Failed Login Attempt</strong><br>
                                <small class="text-muted">Multiple failed login attempts detected - 10 hours ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="action-btn" onclick="performBackup()">
                            <i class="fas fa-download me-2"></i>Create Backup
                        </button>
                        <button class="action-btn success" onclick="addInventoryItem()">
                            <i class="fas fa-plus me-2"></i>Add Inventory Item
                        </button>
                        <button class="action-btn" onclick="verifyDocuments()">
                            <i class="fas fa-check-double me-2"></i>Bulk Verify Documents
                        </button>
                        <button class="action-btn danger" onclick="viewSecurityLogs()">
                            <i class="fas fa-shield-alt me-2"></i>View Security Logs
                        </button>
                        <button class="action-btn" onclick="systemMaintenance()">
                            <i class="fas fa-tools me-2"></i>System Maintenance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Inventory Status -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-boxes me-2"></i>Current Inventory Status</h5>
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                        <i class="fas fa-plus me-2"></i>Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="inventory-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Computers & Laptops</h6>
                                        <small class="text-muted">Desktop: 25, Laptops: 15</small>
                                    </div>
                                    <span class="status-badge status-available">Available</span>
                                </div>
                            </div>
                            
                            <div class="inventory-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Stationery Supplies</h6>
                                        <small class="text-muted">Pens, Pencils, Paper, etc.</small>
                                    </div>
                                    <span class="status-badge status-low">Low Stock</span>
                                </div>
                            </div>
                            
                            <div class="inventory-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Sports Equipment</h6>
                                        <small class="text-muted">Balls, Bats, Nets, etc.</small>
                                    </div>
                                    <span class="status-badge status-available">Available</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="inventory-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Laboratory Equipment</h6>
                                        <small class="text-muted">Microscopes, Chemicals, etc.</small>
                                    </div>
                                    <span class="status-badge status-maintenance">Maintenance</span>
                                </div>
                            </div>
                            
                            <div class="inventory-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Furniture</h6>
                                        <small class="text-muted">Desks, Chairs, Tables</small>
                                    </div>
                                    <span class="status-badge status-available">Available</span>
                                </div>
                            </div>
                            
                            <div class="inventory-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Audio/Visual Equipment</h6>
                                        <small class="text-muted">Projectors, Speakers, etc.</small>
                                    </div>
                                    <span class="status-badge status-out">Out of Stock</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Verification Status -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-file-check me-2"></i>Document Verification Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="document-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Student Admission Documents</h6>
                                        <small class="text-muted">Birth certificates, Previous records</small>
                                    </div>
                                    <span class="verification-badge verified">Verified</span>
                                </div>
                            </div>
                            
                            <div class="document-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Teacher Qualification Certificates</h6>
                                        <small class="text-muted">Degrees, Experience certificates</small>
                                    </div>
                                    <span class="verification-badge pending">Pending</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="document-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Staff Identity Documents</h6>
                                        <small class="text-muted">ID proofs, Address proofs</small>
                                    </div>
                                    <span class="verification-badge verified">Verified</span>
                                </div>
                            </div>
                            
                            <div class="document-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Medical Certificates</h6>
                                        <small class="text-muted">Health checkup reports</small>
                                    </div>
                                    <span class="verification-badge expired">Expired</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Inventory Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Inventory Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addInventoryForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Item Name</label>
                                <input type="text" class="form-control" name="item_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-control" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="electronics">Electronics</option>
                                    <option value="stationery">Stationery</option>
                                    <option value="furniture">Furniture</option>
                                    <option value="sports">Sports Equipment</option>
                                    <option value="laboratory">Laboratory</option>
                                    <option value="books">Books</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Unit Price</label>
                                <input type="number" class="form-control" name="unit_price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Condition</label>
                                <select class="form-control" name="condition" required>
                                    <option value="">Select Condition</option>
                                    <option value="new">New</option>
                                    <option value="good">Good</option>
                                    <option value="fair">Fair</option>
                                    <option value="poor">Poor</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" name="supplier">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" class="form-control" name="purchase_date">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Enter item description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="saveInventoryItem()">
                    <i class="fas fa-save me-2"></i>Add Item
                </button>
            </div>
        </div>
    </div>
</div>

<!-- System Settings Modal -->
<div class="modal fade" id="systemSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title"><i class="fas fa-cog me-2"></i>System Settings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="settings-section">
                    <h6>General Settings</h6>
                    <div class="settings-toggle">
                        <div>
                            <strong>Automatic Backups</strong><br>
                            <small class="text-muted">Enable daily automatic system backups</small>
                        </div>
                        <div class="toggle-switch active" onclick="toggleSetting(this)"></div>
                    </div>
                    <div class="settings-toggle">
                        <div>
                            <strong>Email Notifications</strong><br>
                            <small class="text-muted">Send email notifications for important events</small>
                        </div>
                        <div class="toggle-switch active" onclick="toggleSetting(this)"></div>
                    </div>
                    <div class="settings-toggle">
                        <div>
                            <strong>SMS Alerts</strong><br>
                            <small class="text-muted">Send SMS alerts for critical notifications</small>
                        </div>
                        <div class="toggle-switch" onclick="toggleSetting(this)"></div>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h6>Security Settings</h6>
                    <div class="settings-toggle">
                        <div>
                            <strong>Two-Factor Authentication</strong><br>
                            <small class="text-muted">Require 2FA for admin accounts</small>
                        </div>
                        <div class="toggle-switch active" onclick="toggleSetting(this)"></div>
                    </div>
                    <div class="settings-toggle">
                        <div>
                            <strong>Session Timeout</strong><br>
                            <small class="text-muted">Auto-logout after 30 minutes of inactivity</small>
                        </div>
                        <div class="toggle-switch active" onclick="toggleSetting(this)"></div>
                    </div>
                    <div class="settings-toggle">
                        <div>
                            <strong>Login Attempt Monitoring</strong><br>
                            <small class="text-muted">Monitor and block suspicious login attempts</small>
                        </div>
                        <div class="toggle-switch active" onclick="toggleSetting(this)"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary-custom" onclick="saveSettings()">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Animate counters
    animateCounters();
});

function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    counters.forEach(counter => {
        const target = parseInt(counter.textContent);
        const increment = target / 100;
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target;
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current);
            }
        }, 20);
    });
}

function openInventoryManager() {
    alert('Opening Inventory Management System...');
    // Here you would redirect to inventory management page
}

function openDocumentVerification() {
    alert('Opening Document Verification System...');
    // Here you would redirect to document verification page
}

function openSystemSettings() {
    $('#systemSettingsModal').modal('show');
}

function openBackupManager() {
    alert('Opening Backup Management System...');
    // Here you would redirect to backup management page
}

function openUserManager() {
    alert('Opening User Management System...');
    // Here you would redirect to user management page
}

function openSecurityCenter() {
    alert('Opening Security Center...');
    // Here you would redirect to security center page
}

function performBackup() {
    if (confirm('Are you sure you want to create a system backup? This may take several minutes.')) {
        alert('Backup process initiated. You will be notified when complete.');
        // Here you would trigger backup process
    }
}

function addInventoryItem() {
    $('#addInventoryModal').modal('show');
}

function saveInventoryItem() {
    const form = document.getElementById('addInventoryForm');
    const formData = new FormData(form);
    
    // Here you would typically send the data to your backend
    console.log('Adding inventory item:', Object.fromEntries(formData));
    
    // Show success message
    alert('Inventory item added successfully!');
    $('#addInventoryModal').modal('hide');
    form.reset();
}

function verifyDocuments() {
    alert('Initiating bulk document verification...');
    // Here you would trigger bulk verification process
}

function viewSecurityLogs() {
    alert('Opening Security Logs...');
    // Here you would redirect to security logs page
}

function systemMaintenance() {
    if (confirm('Are you sure you want to put the system in maintenance mode? This will temporarily disable access for all users.')) {
        alert('System maintenance mode activated.');
        // Here you would activate maintenance mode
    }
}

function generateSystemReport() {
    alert('Generating comprehensive system report...');
    // Here you would generate and download system report
}

function toggleSetting(element) {
    element.classList.toggle('active');
}

function saveSettings() {
    alert('System settings saved successfully!');
    $('#systemSettingsModal').modal('hide');
    // Here you would save settings to backend
}
</script>
@endsection