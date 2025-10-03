@extends('layouts.app')

@section('title', 'Add New Item')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Add New Item</h1>
                    <p class="text-muted">Create a new inventory item</p>
                </div>
                <div>
                    <a href="{{ route('inventory.items.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Items
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form id="createItemForm" enctype="multipart/form-data">
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
                                    <input type="text" class="form-control" id="name" name="name" required>
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
                                        <input type="text" class="form-control" id="barcode" name="barcode">
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
                                        <input type="number" class="form-control" id="unit_price" name="unit_price" step="0.01" min="0" required>
                                    </div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter item description..."></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="specifications" class="form-label">Specifications</label>
                                    <textarea class="form-control" id="specifications" name="specifications" rows="3" placeholder="Technical specifications..."></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional notes..."></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Stock Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_stock" class="form-label">Current Stock *</label>
                                    <input type="number" class="form-control" id="current_stock" name="current_stock" min="0" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="minimum_stock" class="form-label">Minimum Stock Level *</label>
                                    <input type="number" class="form-control" id="minimum_stock" name="minimum_stock" min="0" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="maximum_stock" class="form-label">Maximum Stock Level</label>
                                    <input type="number" class="form-control" id="maximum_stock" name="maximum_stock" min="0">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unit_of_measurement" class="form-label">Unit of Measurement</label>
                                    <select class="form-select" id="unit_of_measurement" name="unit_of_measurement">
                                        <option value="piece">Piece</option>
                                        <option value="kg">Kilogram</option>
                                        <option value="gram">Gram</option>
                                        <option value="liter">Liter</option>
                                        <option value="meter">Meter</option>
                                        <option value="box">Box</option>
                                        <option value="pack">Pack</option>
                                        <option value="set">Set</option>
                                        <option value="pair">Pair</option>
                                        <option value="dozen">Dozen</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Storage Location</label>
                                    <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Warehouse A, Shelf 1">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Asset Information (conditionally shown) -->
                <div class="card shadow mb-4" id="assetInfoCard" style="display: none;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Asset Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="serial_number" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="model_number" class="form-label">Model Number</label>
                                    <input type="text" class="form-control" id="model_number" name="model_number">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="manufacturer" class="form-label">Manufacturer</label>
                                    <input type="text" class="form-control" id="manufacturer" name="manufacturer">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                                    <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="depreciation_rate" class="form-label">Depreciation Rate (%)</label>
                                    <input type="number" class="form-control" id="depreciation_rate" name="depreciation_rate" step="0.01" min="0" max="100">
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
                                <input class="form-check-input" type="radio" name="is_asset" id="consumable" value="0" checked>
                                <label class="form-check-label" for="consumable">
                                    <i class="fas fa-box"></i> Consumable
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="is_asset" id="asset" value="1">
                                <label class="form-check-label" for="asset">
                                    <i class="fas fa-desktop"></i> Asset
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="available">Available</option>
                                <option value="allocated">Allocated</option>
                                <option value="maintenance">Under Maintenance</option>
                                <option value="damaged">Damaged</option>
                                <option value="lost">Lost</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
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
                            <input type="text" class="form-control" id="vendor_part_number" name="vendor_part_number">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Item Image</h6>
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
                            <i class="fas fa-save"></i> Create Item
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-block" onclick="saveDraft()">
                            <i class="fas fa-file-alt"></i> Save as Draft
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
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
    $('#createItemForm').on('submit', function(e) {
        e.preventDefault();
        createItem();
    });
    
    // Auto-calculate warranty expiry based on purchase date
    $('#purchase_date').on('change', function() {
        const purchaseDate = new Date($(this).val());
        if (purchaseDate) {
            // Default to 1 year warranty
            const warrantyExpiry = new Date(purchaseDate);
            warrantyExpiry.setFullYear(warrantyExpiry.getFullYear() + 1);
            $('#warranty_expiry').val(warrantyExpiry.toISOString().split('T')[0]);
        }
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
                    options += `<option value="${category.id}">${category.name}</option>`;
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
                    options += `<option value="${vendor.id}">${vendor.name}</option>`;
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

function createItem() {
    const formData = new FormData(document.getElementById('createItemForm'));
    
    // Clear previous validation errors
    $('.form-control, .form-select').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    $.ajax({
        url: '{{ route("inventory.items.store") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showToast('Item created successfully', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("inventory.items.index") }}';
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
                const message = xhr.responseJSON?.message || 'Error creating item';
                showToast(message, 'error');
            }
        }
    });
}

function saveDraft() {
    const formData = new FormData(document.getElementById('createItemForm'));
    formData.append('is_draft', '1');
    
    $.ajax({
        url: '{{ route("inventory.items.store") }}',
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