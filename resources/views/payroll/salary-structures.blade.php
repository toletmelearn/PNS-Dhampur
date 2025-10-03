@extends('layouts.app')

@section('title', 'Salary Structures Management')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Salary Structures Management</h2>
                    <p class="text-muted">Manage employee salary structures, allowances, and deductions</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#salaryStructureModal">
                    <i class="fas fa-plus me-2"></i>Add New Structure
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Structures</h6>
                            <h3 class="mb-0" id="totalStructures">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-layer-group fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Active Structures</h6>
                            <h3 class="mb-0" id="activeStructures">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Grade Levels</h6>
                            <h3 class="mb-0" id="gradeLevels">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-stairs fa-2x opacity-75"></i>
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
                            <h6 class="card-title">Avg. Gross Salary</h6>
                            <h3 class="mb-0" id="avgGrossSalary">₹0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-rupee-sign fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="gradeFilter" class="form-label">Grade Level</label>
                    <select class="form-select" id="gradeFilter">
                        <option value="">All Grades</option>
                        <option value="1">Grade 1</option>
                        <option value="2">Grade 2</option>
                        <option value="3">Grade 3</option>
                        <option value="4">Grade 4</option>
                        <option value="5">Grade 5</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="effectiveDateFilter" class="form-label">Effective From</label>
                    <input type="date" class="form-control" id="effectiveDateFilter">
                </div>
                <div class="col-md-3">
                    <label for="searchFilter" class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchFilter" placeholder="Search structures...">
                </div>
            </div>
        </div>
    </div>

    <!-- Salary Structures Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Salary Structures</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="salaryStructuresTable">
                    <thead>
                        <tr>
                            <th>Structure Name</th>
                            <th>Grade Level</th>
                            <th>Basic Salary</th>
                            <th>Gross Salary</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th>Effective From</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Salary Structure Modal -->
<div class="modal fade" id="salaryStructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add/Edit Salary Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="salaryStructureForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="structureName" class="form-label">Structure Name</label>
                                <input type="text" class="form-control" id="structureName" name="structure_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gradeLevel" class="form-label">Grade Level</label>
                                <select class="form-select" id="gradeLevel" name="grade_level" required>
                                    <option value="">Select Grade</option>
                                    <option value="1">Grade 1</option>
                                    <option value="2">Grade 2</option>
                                    <option value="3">Grade 3</option>
                                    <option value="4">Grade 4</option>
                                    <option value="5">Grade 5</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="basicSalary" class="form-label">Basic Salary</label>
                                <input type="number" class="form-control" id="basicSalary" name="basic_salary" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="effectiveFrom" class="form-label">Effective From</label>
                                <input type="date" class="form-control" id="effectiveFrom" name="effective_from" required>
                            </div>
                        </div>
                    </div>

                    <!-- Allowances Section -->
                    <h6 class="mb-3">Allowances</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="hra" class="form-label">HRA</label>
                                <input type="number" class="form-control" id="hra" name="allowances[hra]" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="da" class="form-label">DA</label>
                                <input type="number" class="form-control" id="da" name="allowances[da]" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="ta" class="form-label">TA</label>
                                <input type="number" class="form-control" id="ta" name="allowances[ta]" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="medical" class="form-label">Medical Allowance</label>
                                <input type="number" class="form-control" id="medical" name="allowances[medical]" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="special" class="form-label">Special Allowance</label>
                                <input type="number" class="form-control" id="special" name="allowances[special]" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="other" class="form-label">Other Allowance</label>
                                <input type="number" class="form-control" id="other" name="allowances[other]" step="0.01">
                            </div>
                        </div>
                    </div>

                    <!-- Deductions Section -->
                    <h6 class="mb-3">Deductions</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="pf" class="form-label">PF (%)</label>
                                <input type="number" class="form-control" id="pf" name="deductions[pf]" step="0.01" value="12">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="esi" class="form-label">ESI (%)</label>
                                <input type="number" class="form-control" id="esi" name="deductions[esi]" step="0.01" value="0.75">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="professionalTax" class="form-label">Professional Tax</label>
                                <input type="number" class="form-control" id="professionalTax" name="deductions[professional_tax]" step="0.01" value="200">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="effectiveTo" class="form-label">Effective To (Optional)</label>
                                <input type="date" class="form-control" id="effectiveTo" name="effective_to">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Structure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.5rem;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.modal-lg {
    max-width: 800px;
}

.opacity-75 {
    opacity: 0.75;
}
</style>

<script>
$(document).ready(function() {
    let salaryStructuresTable;
    let editingStructureId = null;

    // Initialize DataTable
    salaryStructuresTable = $('#salaryStructuresTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route("payroll.salary-structures") }}',
            type: 'GET'
        },
        columns: [
            { data: 'structure_name', name: 'structure_name' },
            { data: 'grade_level', name: 'grade_level' },
            { 
                data: 'basic_salary', 
                name: 'basic_salary',
                render: function(data) {
                    return '₹' + parseFloat(data).toLocaleString('en-IN');
                }
            },
            { 
                data: 'gross_salary', 
                name: 'gross_salary',
                render: function(data) {
                    return '₹' + parseFloat(data).toLocaleString('en-IN');
                }
            },
            { 
                data: 'net_salary', 
                name: 'net_salary',
                render: function(data) {
                    return '₹' + parseFloat(data).toLocaleString('en-IN');
                }
            },
            { 
                data: 'status', 
                name: 'status',
                render: function(data) {
                    let badgeClass = data === 'active' ? 'bg-success' : 
                                   data === 'inactive' ? 'bg-danger' : 'bg-warning';
                    return `<span class="badge ${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                }
            },
            { 
                data: 'effective_from', 
                name: 'effective_from',
                render: function(data) {
                    return new Date(data).toLocaleDateString('en-IN');
                }
            },
            { 
                data: 'id', 
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary edit-structure" data-id="${data}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info view-structure" data-id="${data}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-structure" data-id="${data}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Load statistics
    loadStatistics();

    // Filter handlers
    $('#statusFilter, #gradeFilter, #effectiveDateFilter, #searchFilter').on('change keyup', function() {
        salaryStructuresTable.draw();
    });

    // Form submission
    $('#salaryStructureForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let url = editingStructureId ? 
            `{{ route('payroll.salary-structures.update', '') }}/${editingStructureId}` : 
            '{{ route("payroll.salary-structures.store") }}';
        
        if (editingStructureId) {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#salaryStructureModal').modal('hide');
                salaryStructuresTable.ajax.reload();
                loadStatistics();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Salary structure saved successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                let errors = xhr.responseJSON?.errors || {};
                let errorMessage = 'Please check the form for errors.';
                
                if (Object.keys(errors).length > 0) {
                    errorMessage = Object.values(errors)[0][0];
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            }
        });
    });

    // Edit structure
    $(document).on('click', '.edit-structure', function() {
        editingStructureId = $(this).data('id');
        
        // Load structure data and populate form
        $.get(`{{ route('payroll.salary-structures') }}/${editingStructureId}`, function(data) {
            $('#structureName').val(data.structure_name);
            $('#gradeLevel').val(data.grade_level);
            $('#basicSalary').val(data.basic_salary);
            $('#effectiveFrom').val(data.effective_from);
            $('#effectiveTo').val(data.effective_to);
            $('#status').val(data.status);
            
            // Populate allowances
            if (data.allowances) {
                $('#hra').val(data.allowances.hra || '');
                $('#da').val(data.allowances.da || '');
                $('#ta').val(data.allowances.ta || '');
                $('#medical').val(data.allowances.medical || '');
                $('#special').val(data.allowances.special || '');
                $('#other').val(data.allowances.other || '');
            }
            
            // Populate deductions
            if (data.deductions) {
                $('#pf').val(data.deductions.pf || '12');
                $('#esi').val(data.deductions.esi || '0.75');
                $('#professionalTax').val(data.deductions.professional_tax || '200');
            }
            
            $('#salaryStructureModal').modal('show');
        });
    });

    // Delete structure
    $(document).on('click', '.delete-structure', function() {
        let structureId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will permanently delete the salary structure.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ route('payroll.salary-structures.destroy', '') }}/${structureId}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        salaryStructuresTable.ajax.reload();
                        loadStatistics();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Salary structure has been deleted.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to delete salary structure.'
                        });
                    }
                });
            }
        });
    });

    // Reset form when modal is hidden
    $('#salaryStructureModal').on('hidden.bs.modal', function() {
        $('#salaryStructureForm')[0].reset();
        editingStructureId = null;
    });

    function loadStatistics() {
        $.get('{{ route("payroll.api.statistics") }}', function(data) {
            $('#totalStructures').text(data.total_structures || 0);
            $('#activeStructures').text(data.active_structures || 0);
            $('#gradeLevels').text(data.grade_levels || 0);
            $('#avgGrossSalary').text('₹' + (data.avg_gross_salary || 0).toLocaleString('en-IN'));
        });
    }
});
</script>
@endsection