<!-- Create Substitution Modal -->
<div class="modal fade" id="createSubstitutionModal" tabindex="-1" aria-labelledby="createSubstitutionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSubstitutionModalLabel">Create New Substitution Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createSubstitutionForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="original_teacher_id" class="form-label">Original Teacher *</label>
                                <select class="form-select" id="original_teacher_id" name="original_teacher_id" required>
                                    <option value="">Select Teacher</option>
                                    @foreach(\App\Models\Teacher::where('is_active', true)->get() as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="class_id" class="form-label">Class *</label>
                                <select class="form-select" id="class_id" name="class_id" required>
                                    <option value="">Select Class</option>
                                    @foreach(\App\Models\ClassModel::all() as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="subject_id" class="form-label">Subject *</label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    @foreach(\App\Models\Subject::all() as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="substitution_date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="substitution_date" name="substitution_date" required min="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="period_number" class="form-label">Period *</label>
                                <select class="form-select" id="period_number" name="period_number" required>
                                    <option value="">Select Period</option>
                                    @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}">Period {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_time" class="form-label">End Time *</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority *</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_emergency" name="is_emergency">
                                    <label class="form-check-label" for="is_emergency">
                                        Emergency Substitution
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_assign" name="auto_assign" checked>
                                    <label class="form-check-label" for="auto_assign">
                                        Auto-assign substitute
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason *</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2" required placeholder="Reason for substitution..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional information..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="preparation_materials" class="form-label">Preparation Materials</label>
                        <textarea class="form-control" id="preparation_materials" name="preparation_materials" rows="3" placeholder="Materials, lesson plans, or instructions for the substitute teacher..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Substitution</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Substitute Modal -->
<div class="modal fade" id="assignSubstituteModal" tabindex="-1" aria-labelledby="assignSubstituteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignSubstituteModalLabel">Assign Substitute Teacher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="availableTeachersLoading" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Finding available teachers...</p>
                </div>
                <div id="availableTeachersContent" style="display: none;">
                    <div class="mb-3">
                        <h6>Available Teachers</h6>
                        <div id="availableTeachersList"></div>
                    </div>
                    <form id="assignSubstituteForm">
                        <input type="hidden" id="assign_substitution_id" name="substitution_id">
                        <div class="mb-3">
                            <label for="substitute_teacher_id" class="form-label">Select Substitute Teacher</label>
                            <select class="form-select" id="substitute_teacher_id" name="substitute_teacher_id" required>
                                <option value="">Choose a teacher...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="assign_notes" class="form-label">Assignment Notes</label>
                            <textarea class="form-control" id="assign_notes" name="notes" rows="2" placeholder="Any special instructions or notes..."></textarea>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAssignBtn" onclick="confirmAssignment()">Assign Teacher</button>
            </div>
        </div>
    </div>
</div>

<!-- Find Available Teachers Modal -->
<div class="modal fade" id="findTeachersModal" tabindex="-1" aria-labelledby="findTeachersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="findTeachersModalLabel">Find Available Teachers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="findTeachersForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="search_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="search_date" name="date" required min="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="search_start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="search_start_time" name="start_time" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="search_end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="search_end_time" name="end_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="search_subject_id" class="form-label">Subject (Optional)</label>
                                <select class="form-select" id="search_subject_id" name="subject_id">
                                    <option value="">Any Subject</option>
                                    @foreach(\App\Models\Subject::all() as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="search_class_id" class="form-label">Class (Optional)</label>
                                <select class="form-select" id="search_class_id" name="class_id">
                                    <option value="">Any Class</option>
                                    @foreach(\App\Models\ClassModel::all() as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Search Available Teachers</button>
                    </div>
                </form>

                <div id="searchResults" class="mt-4" style="display: none;">
                    <h6>Available Teachers</h6>
                    <div id="searchResultsList"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Substitution Details Modal -->
<div class="modal fade" id="substitutionDetailsModal" tabindex="-1" aria-labelledby="substitutionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="substitutionDetailsModalLabel">Substitution Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be populated dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Report Generation Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Generate Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reportForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select class="form-select" id="report_type" name="report_type" required>
                            <option value="daily">Daily Report</option>
                            <option value="weekly">Weekly Report</option>
                            <option value="monthly">Monthly Report</option>
                            <option value="teacher_performance">Teacher Performance</option>
                            <option value="substitution_analytics">Substitution Analytics</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="report_start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="report_start_date" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="report_end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="report_end_date" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="report_teacher_id" class="form-label">Specific Teacher (Optional)</label>
                        <select class="form-select" id="report_teacher_id" name="teacher_id">
                            <option value="">All Teachers</option>
                            @foreach(\App\Models\Teacher::where('is_active', true)->get() as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="report_format" class="form-label">Format</label>
                        <select class="form-select" id="report_format" name="format" required>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Create Substitution Form Handler
document.getElementById('createSubstitutionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Convert checkbox values
    data.is_emergency = document.getElementById('is_emergency').checked;
    data.auto_assign = document.getElementById('auto_assign').checked;
    
    fetch('/substitutions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            $('#createSubstitutionModal').modal('hide');
            this.reset();
            refreshDashboard();
        } else {
            showAlert(data.message || 'Error creating substitution', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error creating substitution', 'danger');
    });
});

// Find Teachers Form Handler
document.getElementById('findTeachersForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const params = new URLSearchParams(formData);
    
    document.getElementById('searchResults').style.display = 'none';
    
    fetch(`/substitutions/available-teachers?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.available_teachers);
            } else {
                showAlert('Error finding available teachers', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error finding available teachers', 'danger');
        });
});

// Report Form Handler
document.getElementById('reportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const params = new URLSearchParams(formData);
    
    window.open(`/substitutions/reports?${params}`, '_blank');
    $('#reportModal').modal('hide');
});

function displaySearchResults(teachers) {
    const resultsContainer = document.getElementById('searchResultsList');
    
    if (teachers.length === 0) {
        resultsContainer.innerHTML = '<p class="text-muted">No available teachers found for the specified criteria.</p>';
    } else {
        let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Name</th><th>Subjects</th><th>Today\'s Substitutions</th><th>Reliability</th><th>Actions</th></tr></thead><tbody>';
        
        teachers.forEach(teacher => {
            html += `
                <tr>
                    <td>${teacher.name}</td>
                    <td><small>${teacher.subjects.join(', ')}</small></td>
                    <td><span class="badge badge-info">${teacher.today_substitutions}</span></td>
                    <td><span class="badge badge-${teacher.reliability_score >= 80 ? 'success' : teacher.reliability_score >= 60 ? 'warning' : 'danger'}">${teacher.reliability_score}%</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="contactTeacher(${teacher.id})">
                            <i class="fas fa-phone"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        resultsContainer.innerHTML = html;
    }
    
    document.getElementById('searchResults').style.display = 'block';
}

function confirmAssignment() {
    const form = document.getElementById('assignSubstituteForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const substitutionId = document.getElementById('assign_substitution_id').value;
    
    fetch(`/substitutions/${substitutionId}/assign`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            $('#assignSubstituteModal').modal('hide');
            refreshDashboard();
        } else {
            showAlert(data.message || 'Error assigning substitute', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error assigning substitute', 'danger');
    });
}

function contactTeacher(teacherId) {
    // This would open a contact modal or initiate contact
    showAlert('Contact feature will be implemented', 'info');
}

// Set default dates
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('substitution_date').value = today;
    document.getElementById('search_date').value = today;
    document.getElementById('report_start_date').value = today;
    document.getElementById('report_end_date').value = today;
});
</script>