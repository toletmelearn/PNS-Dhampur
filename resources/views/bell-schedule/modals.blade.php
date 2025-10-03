<!-- Add Bell Timing Modal -->
<div class="modal fade" id="addBellTimingModal" tabindex="-1" aria-labelledby="addBellTimingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBellTimingModalLabel">Add Bell Timing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bellTimingForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bell_name" class="form-label">Bell Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="bell_name" name="name" required>
                                <div class="form-text">e.g., Morning Assembly, First Period, Lunch Break</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bell_time" class="form-label">Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="bell_time" name="time" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bell_type" class="form-label">Bell Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="bell_type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="start">Start Bell</option>
                                    <option value="end">End Bell</option>
                                    <option value="break">Break Bell</option>
                                    <option value="assembly">Assembly Bell</option>
                                    <option value="period">Period Bell</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bell_season" class="form-label">Season <span class="text-danger">*</span></label>
                                <select class="form-select" id="bell_season" name="season" required>
                                    <option value="">Select Season</option>
                                    <option value="winter">Winter Schedule</option>
                                    <option value="summer">Summer Schedule</option>
                                    <option value="both">Both Seasons</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bell_order" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="bell_order" name="order" min="1">
                                <div class="form-text">Order in which this bell appears in the schedule</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bell_duration" class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" id="bell_duration" name="duration" min="1">
                                <div class="form-text">Duration of this period/break</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="bell_description" class="form-label">Description</label>
                        <textarea class="form-control" id="bell_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bell_active" name="is_active" checked>
                            <label class="form-check-label" for="bell_active">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="create_notifications" name="create_notifications" checked>
                            <label class="form-check-label" for="create_notifications">
                                Create default notifications for this bell timing
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Bell Timing</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Notification Modal -->
<div class="modal fade" id="addNotificationModal" tabindex="-1" aria-labelledby="addNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNotificationModalLabel">Add Bell Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="notificationForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notification_bell_timing" class="form-label">Bell Timing <span class="text-danger">*</span></label>
                                <select class="form-select" id="notification_bell_timing" name="bell_timing_id" required>
                                    <option value="">Select Bell Timing</option>
                                    <!-- Dynamic options will be loaded -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notification_type" class="form-label">Notification Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="notification_type" name="notification_type" required>
                                    <option value="">Select Type</option>
                                    <option value="before">Before Bell</option>
                                    <option value="during">During Bell</option>
                                    <option value="after">After Bell</option>
                                    <option value="reminder">Reminder</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notification_title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="notification_title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notification_priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" id="notification_priority" name="priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notification_message" class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="notification_message" name="message" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notification_time" class="form-label">Notification Time</label>
                                <input type="number" class="form-control" id="notification_time" name="notification_time" min="0" max="60" value="0">
                                <div class="form-text">Minutes before/after bell time (0 = exact time)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target_audience" class="form-label">Target Audience <span class="text-danger">*</span></label>
                                <select class="form-select" id="target_audience" name="target_audience" required>
                                    <option value="all">All Users</option>
                                    <option value="teachers">Teachers Only</option>
                                    <option value="students">Students Only</option>
                                    <option value="admin">Admin Only</option>
                                    <option value="staff">Staff Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sound_file" class="form-label">Sound File</label>
                                <input type="file" class="form-control" id="sound_file" name="sound_file" accept="audio/*">
                                <div class="form-text">Upload custom sound for this notification</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dismiss_after" class="form-label">Auto Dismiss After (seconds)</label>
                                <input type="number" class="form-control" id="dismiss_after" name="dismiss_after_seconds" min="5" max="300" value="30">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notification_enabled" name="is_enabled" checked>
                                    <label class="form-check-label" for="notification_enabled">
                                        Enabled
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_dismiss" name="auto_dismiss" checked>
                                    <label class="form-check-label" for="auto_dismiss">
                                        Auto Dismiss
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Special Schedule Modal -->
<div class="modal fade" id="specialScheduleModal" tabindex="-1" aria-labelledby="specialScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="specialScheduleModalLabel">Create Special Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="specialScheduleForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_name" class="form-label">Schedule Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="schedule_name" name="name" required>
                                <div class="form-text">e.g., Independence Day, Half Day, Exam Schedule</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="schedule_date" name="date" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_type" class="form-label">Schedule Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="schedule_type" name="schedule_type" required>
                                    <option value="">Select Type</option>
                                    <option value="holiday">Holiday</option>
                                    <option value="half_day">Half Day</option>
                                    <option value="exam_schedule">Exam Schedule</option>
                                    <option value="event">Special Event</option>
                                    <option value="custom">Custom Schedule</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="schedule_priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select" id="schedule_priority" name="priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="schedule_description" class="form-label">Description</label>
                        <textarea class="form-control" id="schedule_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="applies_to" class="form-label">Applies To <span class="text-danger">*</span></label>
                                <select class="form-select" id="applies_to" name="applies_to" required>
                                    <option value="all">All Classes</option>
                                    <option value="primary">Primary Classes</option>
                                    <option value="secondary">Secondary Classes</option>
                                    <option value="specific">Specific Classes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="schedule_active" name="is_active" checked>
                                    <label class="form-check-label" for="schedule_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notification_message" class="form-label">Notification Message</label>
                        <textarea class="form-control" id="notification_message" name="notification_message" rows="2" placeholder="Message to display when this schedule is active"></textarea>
                    </div>
                    
                    <!-- Custom Timings Section -->
                    <div id="custom-timings-section" style="display: none;">
                        <hr>
                        <h6 class="mb-3">Custom Bell Timings</h6>
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCustomTiming()">
                                <i class="fas fa-plus"></i> Add Timing
                            </button>
                        </div>
                        <div id="custom-timings-container">
                            <!-- Dynamic custom timings will be added here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Special Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View/Edit Bell Timing Modal -->
<div class="modal fade" id="viewBellTimingModal" tabindex="-1" aria-labelledby="viewBellTimingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewBellTimingModalLabel">Bell Timing Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="bell-timing-details">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="editBellTiming()">Edit</button>
                <button type="button" class="btn btn-danger" onclick="deleteBellTiming()">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="confirmation-message">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-action-btn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal form handling
$(document).ready(function() {
    // Bell Timing Form
    $('#bellTimingForm').on('submit', function(e) {
        e.preventDefault();
        saveBellTiming();
    });
    
    // Notification Form
    $('#notificationForm').on('submit', function(e) {
        e.preventDefault();
        saveNotification();
    });
    
    // Special Schedule Form
    $('#specialScheduleForm').on('submit', function(e) {
        e.preventDefault();
        saveSpecialSchedule();
    });
    
    // Schedule type change handler
    $('#schedule_type').on('change', function() {
        const type = $(this).val();
        if (type === 'custom' || type === 'half_day' || type === 'exam_schedule') {
            $('#custom-timings-section').show();
        } else {
            $('#custom-timings-section').hide();
        }
    });
    
    // Load bell timings for notification form
    $('#addNotificationModal').on('show.bs.modal', function() {
        loadBellTimingsForNotification();
    });
});

function saveBellTiming() {
    const formData = new FormData($('#bellTimingForm')[0]);
    
    $.ajax({
        url: '/bell-timings',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#addBellTimingModal').modal('hide');
                $('#bellTimingForm')[0].reset();
                showAlert('success', 'Bell timing saved successfully!');
                loadBellTimings();
                loadCurrentSchedule();
            }
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors || {};
            showValidationErrors(errors);
        }
    });
}

function saveNotification() {
    const formData = new FormData($('#notificationForm')[0]);
    
    $.ajax({
        url: '/bell-notifications',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#addNotificationModal').modal('hide');
                $('#notificationForm')[0].reset();
                showAlert('success', 'Notification saved successfully!');
                loadNotifications();
            }
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors || {};
            showValidationErrors(errors);
        }
    });
}

function saveSpecialSchedule() {
    const formData = new FormData($('#specialScheduleForm')[0]);
    
    // Add custom timings if any
    const customTimings = getCustomTimings();
    if (customTimings.length > 0) {
        formData.append('custom_timings', JSON.stringify(customTimings));
    }
    
    $.ajax({
        url: '/special-schedules',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#specialScheduleModal').modal('hide');
                $('#specialScheduleForm')[0].reset();
                $('#custom-timings-container').empty();
                $('#custom-timings-section').hide();
                showAlert('success', 'Special schedule saved successfully!');
                loadSpecialSchedules();
                loadCurrentSchedule();
            }
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors || {};
            showValidationErrors(errors);
        }
    });
}

function addCustomTiming() {
    const container = $('#custom-timings-container');
    const index = container.children().length;
    
    const timingRow = $(`
        <div class="row mb-2 custom-timing-row">
            <div class="col-md-3">
                <input type="time" class="form-control" name="custom_timings[${index}][time]" required>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="custom_timings[${index}][name]" placeholder="Bell name" required>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="custom_timings[${index}][type]" required>
                    <option value="start">Start</option>
                    <option value="end">End</option>
                    <option value="break">Break</option>
                    <option value="period">Period</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCustomTiming(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `);
    
    container.append(timingRow);
}

function removeCustomTiming(button) {
    $(button).closest('.custom-timing-row').remove();
}

function getCustomTimings() {
    const timings = [];
    $('.custom-timing-row').each(function() {
        const row = $(this);
        const timing = {
            time: row.find('input[type="time"]').val(),
            name: row.find('input[type="text"]').val(),
            type: row.find('select').val()
        };
        if (timing.time && timing.name && timing.type) {
            timings.push(timing);
        }
    });
    return timings;
}

function loadBellTimingsForNotification() {
    $.get('/bell-timings')
        .done(function(response) {
            if (response.success) {
                const select = $('#notification_bell_timing');
                select.empty().append('<option value="">Select Bell Timing</option>');
                
                response.data.forEach(function(timing) {
                    select.append(`<option value="${timing.id}">${timing.name} (${timing.time})</option>`);
                });
            }
        });
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('#notification-area').prepend(alert);
    
    setTimeout(function() {
        alert.alert('close');
    }, 5000);
}

function showValidationErrors(errors) {
    let errorMessage = 'Please fix the following errors:<ul>';
    for (const field in errors) {
        errors[field].forEach(function(error) {
            errorMessage += `<li>${error}</li>`;
        });
    }
    errorMessage += '</ul>';
    
    showAlert('error', errorMessage);
}
</script>