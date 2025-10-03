@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Edit Item</h1>
                    <p class="text-muted">Update inventory item information</p>
                </div>
                <div>
                    <a href="{{ route('inventory.items.show', $item->id) }}" class="btn btn-outline-info me-2">
                        <i class="fas fa-eye"></i> View Item
                    </a>
                    <a href="{{ route('inventory.items.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Items
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form id="editItemForm" enctype="multipart/form-data">
        @method('PUT')
        <div class="row">
            <!-- Main Information -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Item Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $item->name }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category *</label>
                                    <select class="form-select" id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="barcode" class="form-label">Barcode/SKU</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="barcode" name="barcode" value="{{ $item->barcode }}">
                                        <button class="btn btn-outline-secondary" type="button" onclick="generateBarcode()">
                                            <i class="fas fa-magic"></i> Generate
                                        </button>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unit_price" class="form-label">Unit Price *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="unit_price" name="unit_price" step="0.01" min="0" value="{{ $item->unit_price }}" required>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter item description...">{{ $item->description }}</textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="specifications" class="form-label">Specifications</label>
                                    <textarea class="form-control" id="specifications" name="specifications" rows="3" placeholder="Technical specifications...">{{ $item->specifications }}</textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes...">{{ $item->notes }}</textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Stock Information</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="showStockHistory()">
                            <i class="fas fa-history"></i> Stock History
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_stock" class="form-label">Current Stock *</label>
                                    <input type="number" class="form-control" id="current_stock" name="current_stock" min="0" value="{{ $item->current_stock }}" required>
                                    <div class="invalid-feedback"></div>
                                    <small class="text-muted">Previous: {{ $item->current_stock }}</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="minimum_stock" class="form-label">Minimum Stock Level *</label>
                                    <input type="number" class="form-control" id="minimum_stock" name="minimum_stock" min="0" value="{{ $item->minimum_stock }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="maximum_stock" class="form-label">Maximum Stock Level</label>
                                    <input type="number" class="form-control" id="maximum_stock" name="maximum_stock" min="0" value="{{ $item->maximum_stock }}">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unit_of_measurement" class="form-label">Unit of Measurement</label>
                                    <select class="form-select" id="unit_of_measurement" name="unit_of_measurement">
                                        <option value="piece" {{ $item->unit_of_measurement == 'piece' ? 'selected' : '' }}>Piece</option>
                                        <option value="kg" {{ $item->unit_of_measurement == 'kg' ? 'selected' : '' }}>Kilogram</option>
                                        <option value="gram" {{ $item->unit_of_measurement == 'gram' ? 'selected' : '' }}>Gram</option>
                                        <option value="liter" {{ $item->unit_of_measurement == 'liter' ? 'selected' : '' }}>Liter</option>
                                        <option value="meter" {{ $item->unit_of_measurement == 'meter' ? 'selected' : '' }}>Meter</option>
                                        <option value="box" {{ $item->unit_of_measurement == 'box' ? 'selected' : '' }}>Box</option>
                                        <option value="pack" {{ $item->unit_of_measurement == 'pack' ? 'selected' : '' }}>Pack</option>
                                        <option value="set" {{ $item->unit_of_measurement == 'set' ? 'selected' : '' }}>Set</option>
                                        <option value="pair" {{ $item->unit_of_measurement == 'pair' ? 'selected' : '' }}>Pair</option>
                                        <option value="dozen" {{ $item->unit_of_measurement == 'dozen' ? 'selected' : '' }}>Dozen</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Storage Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="{{ $item->location }}" placeholder="e.g., Warehouse A, Shelf 1">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Stock Adjustment -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Stock Adjustment</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="adjustment_type" class="form-label">Adjustment Type</label>
                                                    <select class="form-select" id="adjustment_type" name="adjustment_type">
                                                        <option value="">No Adjustment</option>
                                                        <option value="increase">Increase Stock</option>
                                                        <option value="decrease">Decrease Stock</option>
                                                        <option value="set">Set Stock Level</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="adjustment_quantity" class="form-label">Quantity</label>
                                                    <input type="number" class="form-control" id="adjustment_quantity" name="adjustment_quantity" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="adjustment_reason" class="form-label">Reason</label>
                                                    <select class="form-select" id="adjustment_reason" name="adjustment_reason">
                                                        <option value="">Select Reason</option>
                                                        <option value="purchase">Purchase</option>
                                                        <option value="return">Return</option>
                                                        <option value="damage">Damage</option>
                                                        <option value="loss">Loss</option>
                                                        <option value="correction">Correction</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="adjustment_notes" class="form-label">Adjustment Notes</label>
                                            <textarea class="form-control" id="adjustment_notes" name="adjustment_notes" rows="2" placeholder="Additional notes for this adjustment..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asset Information (conditionally shown) -->
                <div class="card shadow mb-4" id="assetInfoCard" style="{{ $item->is_asset ? '' : 'display: none;' }}">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Asset Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="serial_number" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number" value="{{ $item->serial_number }}">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="model_number" class="form-label">Model Number</label>
                                    <input type="text" class="form-control" id="model_number" name="model_number" value="{{ $item->model_number }}">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="manufacturer" class="form-label">Manufacturer</label>
                                    <input type="text" class="form-control" id="manufacturer" name="manufacturer" value="{{ $item->manufacturer }}">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                                    <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry" value="{{ $item->warranty_expiry ? $item->warranty_expiry->format('Y-m-d') : '' }}">
                                    <div class="invalid-feedback"></div>
                                    @if($item->warranty_expiry)
                                        <small class="text-muted">
                                            @if($item->warranty_expiry->isPast())
                                                <span class="text-danger">Warranty expired</span>
                                            @else
                                                Expires in {{ $item->warranty_expiry->diffForHumans() }}
                                            @endif
                                        </small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="depreciation_rate" class="form-label">Depreciation Rate (%)</label>
                                    <input type="number" class="form-control" id="depreciation_rate" name="depreciation_rate" step="0.01" min="0" max="100" value="{{ $item->depreciation_rate }}">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Item Type & Status -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Type & Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Item Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_asset" id="consumable" value="0" {{ !$item->is_asset ? 'checked' : '' }}>
                                <label class="form-check-label" for="consumable">
                                    <i class="fas fa-box"></i> Consumable
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_asset" id="asset" value="1" {{ $item->is_asset ? 'checked' : '' }}>
                                <label class="form-check-label" for="asset">
                                    <i class="fas fa-desktop"></i> Asset
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available" {{ $item->status == 'available' ? 'selected' : '' }}>Available</option>
                                <option value="allocated" {{ $item->status == 'allocated' ? 'selected' : '' }}>Allocated</option>
                                <option value="maintenance" {{ $item->status == 'maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                                <option value="damaged" {{ $item->status == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                <option value="lost" {{ $item->status == 'lost' ? 'selected' : '' }}>Lost</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ $item->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Vendor Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Vendor Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="vendor_id" class="form-label">Preferred Vendor</label>
                            <select class="form-select" id="vendor_id" name="vendor_id">
                                <option value="">Select Vendor</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="vendor_part_number" class="form-label">Vendor Part Number</label>
                            <input type="text" class="form-control" id="vendor_part_number" name="vendor_part_number" value="{{ $item->vendor_part_number }}">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <!-- Current Image -->
                @if($item->image)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Current Image</h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="img-fluid rounded mb-2" style="max-height: 200px;">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCurrentImage()">
                                <i class="fas fa-times"></i> Remove Current Image
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Image Upload -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">{{ $item->image ? 'Replace Image' : 'Add Image' }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">Upload an image for this item (max 2MB)</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        
                        <div id="imagePreview" class="text-center" style="display: none;">
                            <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeImage()">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-save"></i> Update Item
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-block mb-2" onclick="saveDraft()">
                            <i class="fas fa-file-alt"></i> Save as Draft
                        </button>
                        <button type="button" class="btn btn-outline-info btn-block" onclick="previewChanges()">
                            <i class="fas fa-eye"></i> Preview Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
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
<script>
$(document).ready(function() {
    loadCategories();
    loadVendors();
    
    // Toggle asset information card
    $('input[name="is_asset"]').on('change', function() {
        if ($(this).val() === '1') {
            $('#assetInfoCard').show();
        } else {
            $('#assetInfoCard').hide();
        }
    });
    
    // Image preview
    $('#image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#imagePreview').show();
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Form submission
    $('#editItemForm').on('submit', function(e) {
        e.preventDefault();
        updateItem();
    });
    
    // Stock adjustment calculations
    $('#adjustment_type, #adjustment_quantity').on('change', function() {
        calculateNewStock();
    });
});

function loadCategories() {
    $.ajax({
        url: '{{ route("inventory.categories.index") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Select Category</option>';
                response.data.data.forEach(category => {
                    const selected = category.id == {{ $item->category_id ?? 'null' }} ? 'selected' : '';
                    options += `<option value="${category.id}" ${selected}>${category.name}</option>`;
                });
                $('#category_id').html(options);
            }
        },
        error: function(xhr) {
            console.error('Error loading categories:', xhr);
        }
    });
}

function loadVendors() {
    $.ajax({
        url: '{{ route("inventory.vendors.index") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">Select Vendor</option>';
                response.data.data.forEach(vendor => {
                    const selected = vendor.id == {{ $item->vendor_id ?? 'null' }} ? 'selected' : '';
                    options += `<option value="${vendor.id}" ${selected}>${vendor.name}</option>`;
                });
                $('#vendor_id').html(options);
            }
        },
        error: function(xhr) {
            console.error('Error loading vendors:', xhr);
        }
    });
}

function generateBarcode() {
    // Generate a simple barcode based on timestamp and random number
    const timestamp = Date.now().toString().slice(-6);
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    const barcode = `ITM${timestamp}${random}`;
    $('#barcode').val(barcode);
}

function removeImage() {
    $('#image').val('');
    $('#imagePreview').hide();
    $('#previewImg').attr('src', '');
}

function removeCurrentImage() {
    if (confirm('Are you sure you want to remove the current image?')) {
        $.ajax({
            url: '{{ route("inventory.items.remove-image", $item->id) }}',
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast('Image removed successfully', 'success');
                    location.reload();
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error removing image';
                showToast(message, 'error');
            }
        });
    }
}

function calculateNewStock() {
    const currentStock = parseInt($('#current_stock').val()) || 0;
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
        $('#current_stock').val(newStock);
    }
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

function updateItem() {
    const formData = new FormData(document.getElementById('editItemForm'));
    
    // Clear previous validation errors
    $('.form-control, .form-select').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    $.ajax({
        url: '{{ route("inventory.items.update", $item->id) }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showToast('Item updated successfully', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("inventory.items.show", $item->id) }}';
                }, 1500);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                // Validation errors
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const field = $(`#${key}`);
                    field.addClass('is-invalid');
                    field.siblings('.invalid-feedback').text(errors[key][0]);
                });
                showToast('Please correct the validation errors', 'error');
            } else {
                const message = xhr.responseJSON?.message || 'Error updating item';
                showToast(message, 'error');
            }
        }
    });
}

function saveDraft() {
    const formData = new FormData(document.getElementById('editItemForm'));
    formData.append('is_draft', '1');
    
    $.ajax({
        url: '{{ route("inventory.items.update", $item->id) }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showToast('Draft saved successfully', 'success');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'Error saving draft';
            showToast(message, 'error');
        }
    });
}

function previewChanges() {
    // Open item view in new tab to preview changes
    window.open('{{ route("inventory.items.show", $item->id) }}', '_blank');
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