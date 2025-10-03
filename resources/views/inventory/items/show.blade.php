@extends('layouts.app')

@section('title', $item->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">{{ $item->name }}</h1>
                    <p class="text-muted">{{ $item->category->name ?? 'Uncategorized' }} • {{ $item->barcode ?? 'No Barcode' }}</p>
                </div>
                <div>
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('inventory.items.edit', $item->id) }}">
                                <i class="fas fa-edit"></i> Edit Item
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="duplicateItem()">
                                <i class="fas fa-copy"></i> Duplicate Item
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            @if($item->is_asset)
                            <li><a class="dropdown-item" href="#" onclick="allocateAsset()">
                                <i class="fas fa-user-plus"></i> Allocate Asset
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="scheduleMaintenance()">
                                <i class="fas fa-wrench"></i> Schedule Maintenance
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            @endif
                            <li><a class="dropdown-item" href="#" onclick="adjustStock()">
                                <i class="fas fa-plus-minus"></i> Adjust Stock
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="generateQR()">
                                <i class="fas fa-qrcode"></i> Generate QR Code
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="printLabel()">
                                <i class="fas fa-print"></i> Print Label
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteItem()">
                                <i class="fas fa-trash"></i> Delete Item
                            </a></li>
                        </ul>
                    </div>
                    <a href="{{ route('inventory.items.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Items
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Basic Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Name:</td>
                                    <td>{{ $item->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Category:</td>
                                    <td>
                                        @if($item->category)
                                            <a href="{{ route('inventory.categories.show', $item->category->id) }}" class="text-decoration-none">
                                                {{ $item->category->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">Uncategorized</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Barcode/SKU:</td>
                                    <td>
                                        @if($item->barcode)
                                            <code>{{ $item->barcode }}</code>
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('{{ $item->barcode }}')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit Price:</td>
                                    <td>₹{{ number_format($item->unit_price, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Unit of Measurement:</td>
                                    <td>{{ ucfirst($item->unit_of_measurement ?? 'piece') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Type:</td>
                                    <td>
                                        @if($item->is_asset)
                                            <span class="badge bg-info"><i class="fas fa-desktop"></i> Asset</span>
                                        @else
                                            <span class="badge bg-secondary"><i class="fas fa-box"></i> Consumable</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Status:</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'available' => 'success',
                                                'allocated' => 'warning',
                                                'maintenance' => 'info',
                                                'damaged' => 'danger',
                                                'lost' => 'dark'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$item->status] ?? 'secondary' }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Active:</td>
                                    <td>
                                        @if($item->is_active)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-danger">No</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Location:</td>
                                    <td>{{ $item->location ?? 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Created:</td>
                                    <td>{{ $item->created_at->format('M d, Y') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($item->description)
                    <div class="mt-3">
                        <h6 class="fw-bold">Description:</h6>
                        <p class="text-muted">{{ $item->description }}</p>
                    </div>
                    @endif

                    @if($item->specifications)
                    <div class="mt-3">
                        <h6 class="fw-bold">Specifications:</h6>
                        <p class="text-muted">{{ $item->specifications }}</p>
                    </div>
                    @endif

                    @if($item->notes)
                    <div class="mt-3">
                        <h6 class="fw-bold">Notes:</h6>
                        <p class="text-muted">{{ $item->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Stock Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Stock Information</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="showStockHistory()">
                        <i class="fas fa-history"></i> Stock History
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary">{{ $item->current_stock }}</h4>
                                <p class="text-muted mb-0">Current Stock</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-warning">{{ $item->minimum_stock }}</h4>
                                <p class="text-muted mb-0">Minimum Level</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info">{{ $item->maximum_stock ?? 'N/A' }}</h4>
                                <p class="text-muted mb-0">Maximum Level</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success">₹{{ number_format($item->current_stock * $item->unit_price, 2) }}</h4>
                                <p class="text-muted mb-0">Total Value</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Level Indicator -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">Stock Level:</span>
                            @if($item->current_stock <= $item->minimum_stock)
                                <span class="badge bg-danger">Low Stock</span>
                            @elseif($item->maximum_stock && $item->current_stock >= $item->maximum_stock)
                                <span class="badge bg-warning">Overstock</span>
                            @else
                                <span class="badge bg-success">Normal</span>
                            @endif
                        </div>
                        <div class="progress">
                            @php
                                $maxStock = $item->maximum_stock ?? ($item->minimum_stock * 3);
                                $percentage = min(100, ($item->current_stock / $maxStock) * 100);
                                $progressClass = $item->current_stock <= $item->minimum_stock ? 'bg-danger' : 
                                               ($item->maximum_stock && $item->current_stock >= $item->maximum_stock ? 'bg-warning' : 'bg-success');
                            @endphp
                            <div class="progress-bar {{ $progressClass }}" style="width: {{ $percentage }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">0</small>
                            <small class="text-muted">{{ $maxStock }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Information (if applicable) -->
            @if($item->is_asset)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Asset Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Serial Number:</td>
                                    <td>{{ $item->serial_number ?? 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Model Number:</td>
                                    <td>{{ $item->model_number ?? 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Manufacturer:</td>
                                    <td>{{ $item->manufacturer ?? 'Not set' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Purchase Date:</td>
                                    <td>{{ $item->purchase_date ? $item->purchase_date->format('M d, Y') : 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Warranty Expiry:</td>
                                    <td>
                                        @if($item->warranty_expiry)
                                            {{ $item->warranty_expiry->format('M d, Y') }}
                                            @if($item->warranty_expiry->isPast())
                                                <span class="badge bg-danger ms-2">Expired</span>
                                            @else
                                                <span class="badge bg-success ms-2">{{ $item->warranty_expiry->diffForHumans() }}</span>
                                            @endif
                                        @else
                                            Not set
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Depreciation Rate:</td>
                                    <td>{{ $item->depreciation_rate ? $item->depreciation_rate . '%' : 'Not set' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($item->purchase_date && $item->depreciation_rate)
                    <div class="mt-3">
                        <h6 class="fw-bold">Current Value Estimation:</h6>
                        @php
                            $yearsOld = $item->purchase_date->diffInYears(now());
                            $depreciatedValue = $item->unit_price * pow((1 - $item->depreciation_rate / 100), $yearsOld);
                        @endphp
                        <p class="text-muted">
                            Original Value: ₹{{ number_format($item->unit_price, 2) }} |
                            Current Estimated Value: ₹{{ number_format($depreciatedValue, 2) }} |
                            Depreciation: {{ number_format((($item->unit_price - $depreciatedValue) / $item->unit_price) * 100, 1) }}%
                        </p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Vendor Information -->
            @if($item->vendor)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Vendor Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Vendor:</td>
                                    <td>
                                        <a href="{{ route('inventory.vendors.show', $item->vendor->id) }}" class="text-decoration-none">
                                            {{ $item->vendor->name }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Contact:</td>
                                    <td>{{ $item->vendor->contact_person ?? 'Not available' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Part Number:</td>
                                    <td>{{ $item->vendor_part_number ?? 'Not set' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Rating:</td>
                                    <td>
                                        @if($item->vendor->rating)
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= $item->vendor->rating ? 'text-warning' : 'text-muted' }}"></i>
                                            @endfor
                                            ({{ $item->vendor->rating }}/5)
                                        @else
                                            <span class="text-muted">Not rated</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Activity -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                </div>
                <div class="card-body">
                    <div id="recentActivity">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Item Image -->
            @if($item->image)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Item Image</h6>
                </div>
                <div class="card-body text-center">
                    <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="img-fluid rounded">
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="adjustStock()">
                            <i class="fas fa-plus-minus"></i> Adjust Stock
                        </button>
                        @if($item->is_asset)
                        <button class="btn btn-info" onclick="allocateAsset()">
                            <i class="fas fa-user-plus"></i> Allocate Asset
                        </button>
                        <button class="btn btn-warning" onclick="scheduleMaintenance()">
                            <i class="fas fa-wrench"></i> Schedule Maintenance
                        </button>
                        @endif
                        <button class="btn btn-outline-secondary" onclick="generateQR()">
                            <i class="fas fa-qrcode"></i> Generate QR Code
                        </button>
                        <button class="btn btn-outline-secondary" onclick="printLabel()">
                            <i class="fas fa-print"></i> Print Label
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="text-primary" id="totalAllocations">-</h5>
                            <small class="text-muted">Total Allocations</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-success" id="totalMaintenance">-</h5>
                            <small class="text-muted">Maintenance Records</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h5 class="text-warning" id="avgUsageDays">-</h5>
                            <small class="text-muted">Avg Usage (Days)</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-info" id="lastActivity">-</h5>
                            <small class="text-muted">Last Activity</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR Code -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">QR Code</h6>
                </div>
                <div class="card-body text-center">
                    <div id="qrcode"></div>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="downloadQR()">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockAdjustmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stockAdjustmentForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Stock: <strong>{{ $item->current_stock }}</strong></label>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_type" class="form-label">Adjustment Type</label>
                        <select class="form-select" id="adjustment_type" name="adjustment_type" required>
                            <option value="">Select Type</option>
                            <option value="increase">Increase Stock</option>
                            <option value="decrease">Decrease Stock</option>
                            <option value="set">Set Stock Level</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="adjustment_quantity" name="adjustment_quantity" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_reason" class="form-label">Reason</label>
                        <select class="form-select" id="adjustment_reason" name="adjustment_reason" required>
                            <option value="">Select Reason</option>
                            <option value="purchase">Purchase</option>
                            <option value="return">Return</option>
                            <option value="damage">Damage</option>
                            <option value="loss">Loss</option>
                            <option value="correction">Correction</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="adjustment_notes" name="adjustment_notes" rows="3"></textarea>
                    </div>
                    <div class="alert alert-info" id="newStockPreview" style="display: none;">
                        New stock level will be: <strong id="newStockValue">0</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Adjust Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stock History Modal -->
<div class="modal fade" id="stockHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="stockHistoryContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
$(document).ready(function() {
    loadRecentActivity();
    loadStatistics();
    generateQRCode();
    
    // Stock adjustment form
    $('#stockAdjustmentForm').on('submit', function(e) {
        e.preventDefault();
        submitStockAdjustment();
    });
    
    // Calculate new stock preview
    $('#adjustment_type, #adjustment_quantity').on('change', function() {
        calculateNewStock();
    });
});

function loadRecentActivity() {
    $.ajax({
        url: '{{ route("inventory.items.activity", $item->id) }}',
        method: 'GET',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                let content = '<div class="timeline">';
                response.data.forEach(activity => {
                    const date = new Date(activity.created_at).toLocaleDateString();
                    const time = new Date(activity.created_at).toLocaleTimeString();
                    
                    content += `
                        <div class="timeline-item mb-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-circle text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">${activity.description}</h6>
                                    <p class="text-muted mb-1">${activity.details || ''}</p>
                                    <small class="text-muted">${date} at ${time}</small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                content += '</div>';
                $('#recentActivity').html(content);
            } else {
                $('#recentActivity').html('<p class="text-muted text-center">No recent activity</p>');
            }
        },
        error: function(xhr) {
            $('#recentActivity').html('<p class="text-danger text-center">Error loading activity</p>');
        }
    });
}

function loadStatistics() {
    $.ajax({
        url: '{{ route("inventory.items.statistics", $item->id) }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#totalAllocations').text(stats.total_allocations || 0);
                $('#totalMaintenance').text(stats.total_maintenance || 0);
                $('#avgUsageDays').text(stats.avg_usage_days || 0);
                $('#lastActivity').text(stats.last_activity || 'Never');
            }
        },
        error: function(xhr) {
            console.error('Error loading statistics:', xhr);
        }
    });
}

function generateQRCode() {
    const qrData = {
        id: {{ $item->id }},
        name: '{{ $item->name }}',
        barcode: '{{ $item->barcode }}',
        url: '{{ route("inventory.items.show", $item->id) }}'
    };
    
    QRCode.toCanvas(document.getElementById('qrcode'), JSON.stringify(qrData), {
        width: 200,
        margin: 2
    }, function (error) {
        if (error) console.error(error);
    });
}

function downloadQR() {
    const canvas = document.querySelector('#qrcode canvas');
    const link = document.createElement('a');
    link.download = `${{{ $item->id }}}_qr_code.png`;
    link.href = canvas.toDataURL();
    link.click();
}

function adjustStock() {
    $('#stockAdjustmentModal').modal('show');
}

function calculateNewStock() {
    const currentStock = {{ $item->current_stock }};
    const adjustmentType = $('#adjustment_type').val();
    const adjustmentQuantity = parseInt($('#adjustment_quantity').val()) || 0;
    
    let newStock = currentStock;
    
    switch (adjustmentType) {
        case 'increase':
            newStock = currentStock + adjustmentQuantity;
            break;
        case 'decrease':
            newStock = Math.max(0, currentStock - adjustmentQuantity);
            break;
        case 'set':
            newStock = adjustmentQuantity;
            break;
    }
    
    if (adjustmentType && adjustmentQuantity > 0) {
        $('#newStockValue').text(newStock);
        $('#newStockPreview').show();
    } else {
        $('#newStockPreview').hide();
    }
}

function submitStockAdjustment() {
    const formData = new FormData(document.getElementById('stockAdjustmentForm'));
    
    $.ajax({
        url: '{{ route("inventory.items.adjust-stock", $item->id) }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showToast('Stock adjusted successfully', 'success');
                $('#stockAdjustmentModal').modal('hide');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Error adjusting stock';
            showToast(message, 'error');
        }
    });
}

function showStockHistory() {
    $('#stockHistoryModal').modal('show');
    
    $.ajax({
        url: '{{ route("inventory.items.stock-history", $item->id) }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let content = '<div class="table-responsive"><table class="table table-sm">';
                content += '<thead><tr><th>Date</th><th>Type</th><th>Quantity</th><th>Balance</th><th>Reason</th><th>User</th></tr></thead><tbody>';
                
                response.data.forEach(record => {
                    const typeClass = record.type === 'in' ? 'text-success' : 'text-danger';
                    const typeIcon = record.type === 'in' ? 'fa-plus' : 'fa-minus';
                    
                    content += `<tr>
                        <td>${new Date(record.created_at).toLocaleDateString()}</td>
                        <td><i class="fas ${typeIcon} ${typeClass}"></i> ${record.type.toUpperCase()}</td>
                        <td>${record.quantity}</td>
                        <td>${record.balance_after}</td>
                        <td>${record.reason || '-'}</td>
                        <td>${record.user?.name || '-'}</td>
                    </tr>`;
                });
                
                content += '</tbody></table></div>';
                $('#stockHistoryContent').html(content);
            }
        },
        error: function(xhr) {
            $('#stockHistoryContent').html('<div class="alert alert-danger">Error loading stock history</div>');
        }
    });
}

function allocateAsset() {
    window.location.href = '{{ route("inventory.allocations.create") }}?item_id={{ $item->id }}';
}

function scheduleMaintenance() {
    window.location.href = '{{ route("inventory.maintenance.create") }}?item_id={{ $item->id }}';
}

function duplicateItem() {
    if (confirm('Are you sure you want to duplicate this item?')) {
        $.ajax({
            url: '{{ route("inventory.items.duplicate", $item->id) }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast('Item duplicated successfully', 'success');
                    setTimeout(() => {
                        window.location.href = response.redirect_url;
                    }, 1500);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error duplicating item';
                showToast(message, 'error');
            }
        });
    }
}

function generateQR() {
    // QR code is already generated, just show download option
    downloadQR();
}

function printLabel() {
    window.open('{{ route("inventory.items.print-label", $item->id) }}', '_blank');
}

function deleteItem() {
    if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
        $.ajax({
            url: '{{ route("inventory.items.destroy", $item->id) }}',
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast('Item deleted successfully', 'success');
                    setTimeout(() => {
                        window.location.href = '{{ route("inventory.items.index") }}';
                    }, 1500);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error deleting item';
                showToast(message, 'error');
            }
        });
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('Copied to clipboard', 'success');
    });
}

function showToast(message, type) {
    const toast = $(`
        <div class="alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(() => {
        toast.alert('close');
    }, 5000);
}
</script>
@endsection