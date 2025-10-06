@extends('layouts.admin')

@section('title', 'Duplicate Student Records')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-copy mr-2"></i>
                        Duplicate Student Records
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.data-cleanup.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-check"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-ban"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Duplicate Types Tabs -->
                    <ul class="nav nav-tabs" id="duplicatesTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="aadhaar-tab" data-toggle="tab" href="#aadhaar" role="tab">
                                <i class="fas fa-id-card mr-1"></i>
                                Duplicate Aadhaar 
                                <span class="badge badge-danger">{{ count($duplicates['aadhaar'] ?? []) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="admission-tab" data-toggle="tab" href="#admission" role="tab">
                                <i class="fas fa-graduation-cap mr-1"></i>
                                Duplicate Admission No 
                                <span class="badge badge-danger">{{ count($duplicates['admission_no'] ?? []) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="name-dob-tab" data-toggle="tab" href="#name-dob" role="tab">
                                <i class="fas fa-user mr-1"></i>
                                Duplicate Name & DOB 
                                <span class="badge badge-warning">{{ count($duplicates['name_dob'] ?? []) }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="duplicatesTabContent">
                        <!-- Duplicate Aadhaar Tab -->
                        <div class="tab-pane fade show active" id="aadhaar" role="tabpanel">
                            @if(count($duplicates['aadhaar'] ?? []) > 0)
                                @foreach($duplicates['aadhaar'] as $aadhaarGroup)
                                    <div class="card mb-3">
                                        <div class="card-header bg-danger text-white">
                                            <h5 class="mb-0">
                                                <i class="fas fa-id-card mr-2"></i>
                                                Aadhaar: {{ substr($aadhaarGroup[0]->aadhaar, 0, 4) }}****{{ substr($aadhaarGroup[0]->aadhaar, -4) }}
                                                <span class="badge badge-light text-dark ml-2">{{ count($aadhaarGroup) }} records</span>
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <form action="{{ route('admin.data-cleanup.merge-duplicates') }}" method="POST" 
                                                  onsubmit="return confirmMerge(this)">
                                                @csrf
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Primary</th>
                                                                <th>ID</th>
                                                                <th>Name</th>
                                                                <th>Admission No</th>
                                                                <th>Class</th>
                                                                <th>Status</th>
                                                                <th>Created</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($aadhaarGroup as $index => $student)
                                                                <tr class="{{ $index === 0 ? 'table-success' : '' }}">
                                                                    <td>
                                                                        <input type="radio" name="primary_student_id" 
                                                                               value="{{ $student->id }}" 
                                                                               {{ $index === 0 ? 'checked' : '' }} required>
                                                                    </td>
                                                                    <td>{{ $student->id }}</td>
                                                                    <td>
                                                                        <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>
                                                                        @if($student->email)
                                                                            <br><small class="text-muted">{{ $student->email }}</small>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-secondary">{{ $student->admission_no }}</span>
                                                                    </td>
                                                                    <td>
                                                                        @if($student->class)
                                                                            <span class="badge badge-primary">{{ $student->class->name }} - {{ $student->class->section }}</span>
                                                                        @else
                                                                            <span class="badge badge-danger">No Class</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-{{ $student->status === 'active' ? 'success' : 'secondary' }}">
                                                                            {{ ucfirst($student->status) }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <small>{{ $student->created_at->format('Y-m-d') }}</small>
                                                                    </td>
                                                                    <td>
                                                                        <button type="button" class="btn btn-sm btn-info" 
                                                                                onclick="viewStudent({{ $student->id }})">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        @if($index > 0)
                                                                            <input type="checkbox" name="duplicate_student_ids[]" 
                                                                                   value="{{ $student->id }}" checked class="ml-2">
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="text-right mt-2">
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-compress-arrows-alt mr-1"></i>
                                                        Merge Selected Records
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    No duplicate Aadhaar numbers found.
                                </div>
                            @endif
                        </div>

                        <!-- Duplicate Admission Number Tab -->
                        <div class="tab-pane fade" id="admission" role="tabpanel">
                            @if(count($duplicates['admission_no'] ?? []) > 0)
                                @foreach($duplicates['admission_no'] as $admissionGroup)
                                    <div class="card mb-3">
                                        <div class="card-header bg-danger text-white">
                                            <h5 class="mb-0">
                                                <i class="fas fa-graduation-cap mr-2"></i>
                                                Admission No: {{ $admissionGroup[0]->admission_no }}
                                                <span class="badge badge-light text-dark ml-2">{{ count($admissionGroup) }} records</span>
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <form action="{{ route('admin.data-cleanup.merge-duplicates') }}" method="POST" 
                                                  onsubmit="return confirmMerge(this)">
                                                @csrf
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Primary</th>
                                                                <th>ID</th>
                                                                <th>Name</th>
                                                                <th>Aadhaar</th>
                                                                <th>Class</th>
                                                                <th>Status</th>
                                                                <th>Created</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($admissionGroup as $index => $student)
                                                                <tr class="{{ $index === 0 ? 'table-success' : '' }}">
                                                                    <td>
                                                                        <input type="radio" name="primary_student_id" 
                                                                               value="{{ $student->id }}" 
                                                                               {{ $index === 0 ? 'checked' : '' }} required>
                                                                    </td>
                                                                    <td>{{ $student->id }}</td>
                                                                    <td>
                                                                        <strong>{{ $student->first_name }} {{ $student->last_name }}</strong>
                                                                        @if($student->email)
                                                                            <br><small class="text-muted">{{ $student->email }}</small>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($student->aadhaar)
                                                                            <code>{{ substr($student->aadhaar, 0, 4) }}****{{ substr($student->aadhaar, -4) }}</code>
                                                                        @else
                                                                            <span class="text-muted">Not provided</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($student->class)
                                                                            <span class="badge badge-primary">{{ $student->class->name }} - {{ $student->class->section }}</span>
                                                                        @else
                                                                            <span class="badge badge-danger">No Class</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-{{ $student->status === 'active' ? 'success' : 'secondary' }}">
                                                                            {{ ucfirst($student->status) }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <small>{{ $student->created_at->format('Y-m-d') }}</small>
                                                                    </td>
                                                                    <td>
                                                                        <button type="button" class="btn btn-sm btn-info" 
                                                                                onclick="viewStudent({{ $student->id }})">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        @if($index > 0)
                                                                            <input type="checkbox" name="duplicate_student_ids[]" 
                                                                                   value="{{ $student->id }}" checked class="ml-2">
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="text-right mt-2">
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-compress-arrows-alt mr-1"></i>
                                                        Merge Selected Records
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    No duplicate admission numbers found.
                                </div>
                            @endif
                        </div>

                        <!-- Duplicate Name & DOB Tab -->
                        <div class="tab-pane fade" id="name-dob" role="tabpanel">
                            @if(count($duplicates['name_dob'] ?? []) > 0)
                                @foreach($duplicates['name_dob'] as $nameGroup)
                                    <div class="card mb-3">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="mb-0">
                                                <i class="fas fa-user mr-2"></i>
                                                {{ $nameGroup[0]->first_name }} {{ $nameGroup[0]->last_name }} 
                                                ({{ $nameGroup[0]->date_of_birth ? \Carbon\Carbon::parse($nameGroup[0]->date_of_birth)->format('Y-m-d') : 'No DOB' }})
                                                <span class="badge badge-dark ml-2">{{ count($nameGroup) }} records</span>
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                These records have the same name and date of birth. Please review carefully before merging.
                                            </div>
                                            <form action="{{ route('admin.data-cleanup.merge-duplicates') }}" method="POST" 
                                                  onsubmit="return confirmMerge(this)">
                                                @csrf
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Primary</th>
                                                                <th>ID</th>
                                                                <th>Admission No</th>
                                                                <th>Aadhaar</th>
                                                                <th>Email</th>
                                                                <th>Class</th>
                                                                <th>Status</th>
                                                                <th>Created</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($nameGroup as $index => $student)
                                                                <tr class="{{ $index === 0 ? 'table-success' : '' }}">
                                                                    <td>
                                                                        <input type="radio" name="primary_student_id" 
                                                                               value="{{ $student->id }}" 
                                                                               {{ $index === 0 ? 'checked' : '' }} required>
                                                                    </td>
                                                                    <td>{{ $student->id }}</td>
                                                                    <td>
                                                                        <span class="badge badge-secondary">{{ $student->admission_no }}</span>
                                                                    </td>
                                                                    <td>
                                                                        @if($student->aadhaar)
                                                                            <code>{{ substr($student->aadhaar, 0, 4) }}****{{ substr($student->aadhaar, -4) }}</code>
                                                                        @else
                                                                            <span class="text-muted">Not provided</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        {{ $student->email ?? 'Not provided' }}
                                                                    </td>
                                                                    <td>
                                                                        @if($student->class)
                                                                            <span class="badge badge-primary">{{ $student->class->name }} - {{ $student->class->section }}</span>
                                                                        @else
                                                                            <span class="badge badge-danger">No Class</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-{{ $student->status === 'active' ? 'success' : 'secondary' }}">
                                                                            {{ ucfirst($student->status) }}
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <small>{{ $student->created_at->format('Y-m-d') }}</small>
                                                                    </td>
                                                                    <td>
                                                                        <button type="button" class="btn btn-sm btn-info" 
                                                                                onclick="viewStudent({{ $student->id }})">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        @if($index > 0)
                                                                            <input type="checkbox" name="duplicate_student_ids[]" 
                                                                                   value="{{ $student->id }}" class="ml-2">
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="text-right mt-2">
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="fas fa-compress-arrows-alt mr-1"></i>
                                                        Merge Selected Records
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    No duplicate name and date of birth combinations found.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="studentDetails">
                <!-- Student details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function viewStudent(studentId) {
    $('#studentDetails').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    $('#studentModal').modal('show');
    
    // Load student details via AJAX
    fetch(`/admin/students/${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const student = data.student;
                $('#studentDetails').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Personal Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Name:</strong></td><td>${student.first_name} ${student.last_name}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${student.email || 'Not provided'}</td></tr>
                                <tr><td><strong>Phone:</strong></td><td>${student.phone || 'Not provided'}</td></tr>
                                <tr><td><strong>Date of Birth:</strong></td><td>${student.date_of_birth || 'Not provided'}</td></tr>
                                <tr><td><strong>Gender:</strong></td><td>${student.gender || 'Not provided'}</td></tr>
                                <tr><td><strong>Address:</strong></td><td>${student.address || 'Not provided'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Academic Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Admission No:</strong></td><td>${student.admission_no}</td></tr>
                                <tr><td><strong>Class:</strong></td><td>${student.class ? student.class.name + ' - ' + student.class.section : 'No Class'}</td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge badge-${getStatusColor(student.status)}">${student.status}</span></td></tr>
                                <tr><td><strong>Aadhaar:</strong></td><td>${student.aadhaar ? student.aadhaar.substring(0,4) + '****' + student.aadhaar.substring(8) : 'Not provided'}</td></tr>
                                <tr><td><strong>Created:</strong></td><td>${new Date(student.created_at).toLocaleString()}</td></tr>
                                <tr><td><strong>Updated:</strong></td><td>${new Date(student.updated_at).toLocaleString()}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Related Records</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon"><i class="fas fa-calendar-check"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Attendance</span>
                                            <span class="info-box-number">${student.attendance_count || 0}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Fee Records</span>
                                            <span class="info-box-number">${student.fee_records_count || 0}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Results</span>
                                            <span class="info-box-number">${student.results_count || 0}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-danger">
                                        <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Documents</span>
                                            <span class="info-box-number">${student.documents_count || 0}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            } else {
                $('#studentDetails').html('<div class="alert alert-danger">Error loading student details.</div>');
            }
        })
        .catch(error => {
            $('#studentDetails').html('<div class="alert alert-danger">Error loading student details.</div>');
        });
}

function confirmMerge(form) {
    const primaryId = form.querySelector('input[name="primary_student_id"]:checked').value;
    const duplicateIds = Array.from(form.querySelectorAll('input[name="duplicate_student_ids[]"]:checked')).map(cb => cb.value);
    
    if (duplicateIds.length === 0) {
        alert('Please select at least one duplicate record to merge.');
        return false;
    }
    
    const message = `Are you sure you want to merge ${duplicateIds.length} duplicate record(s) into the primary record (ID: ${primaryId})?\n\nThis action cannot be undone. All related data (attendance, fees, results, etc.) will be transferred to the primary record, and the duplicate records will be deleted.`;
    
    return confirm(message);
}

function getStatusColor(status) {
    switch(status) {
        case 'active': return 'success';
        case 'inactive': return 'secondary';
        case 'graduated': return 'info';
        case 'transferred': return 'warning';
        default: return 'dark';
    }
}
</script>
@endpush