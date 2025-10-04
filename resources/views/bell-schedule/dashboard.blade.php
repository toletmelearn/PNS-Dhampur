@extends('layouts.app')

@section('title', 'Bell Schedule Dashboard')

@push('styles')
<link href="{{ asset('css/bell-timing.css') }}" rel="stylesheet">
<link href="{{ asset('css/mobile-notifications.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Bell Schedule Dashboard</h1>
                    <p class="mb-0 text-muted">Intelligent Bell Schedule Management System</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBellTimingModal">
                        <i class="fas fa-plus"></i> Add Bell Timing
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#specialScheduleModal">
                        <i class="fas fa-calendar-alt"></i> Special Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Real-time Clock Display -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bell-clock-container fade-in">
                <div class="season-indicator" id="season-indicator">
                    <span id="current-season-display">Loading...</span>
                </div>
                
                <div class="current-time" id="enhanced-current-time">--:--:--</div>
                <div class="current-date" id="enhanced-current-date">Loading...</div>
                
                <div class="current-period" id="current-period-display">
                    <div class="period-name" id="current-period-name">Loading...</div>
                    <div class="period-time" id="current-period-time">--:-- - --:--</div>
                    
                    <div class="period-progress" id="period-progress-container">
                        <div class="progress-label">Period Progress</div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" id="period-progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="next-period" id="next-period-display">
                    <div class="next-period-label">Next Period</div>
                    <div class="next-period-name" id="next-period-name">Loading...</div>
                    <div class="time-remaining" id="time-remaining">--:--</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Alert (Hidden by default) -->
    <div class="emergency-alert" id="emergency-alert">
        <div class="emergency-title" id="emergency-title">Emergency Alert</div>
        <div class="emergency-message" id="emergency-message">Emergency message will appear here</div>
    </div>

    <!-- Notification Controls -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="notification-controls fade-in">
                <div class="controls-header">
                    <div class="controls-icon">
                        <i class="fas fa-volume-up"></i>
                    </div>
                    <div class="controls-title">Sound & Notification Settings</div>
                </div>
                
                <div class="control-group">
                    <label class="control-label">Sound Notifications</label>
                    <div class="control-row">
                        <label class="toggle-switch">
                            <input type="checkbox" id="sound-enabled" checked>
                            <span class="slider"></span>
                        </label>
                        <span class="ms-2">Enable bell sounds</span>
                    </div>
                </div>
                
                <div class="control-group">
                    <label class="control-label">Volume Control</label>
                    <div class="control-row">
                        <div class="volume-control">
                            <i class="fas fa-volume-down"></i>
                            <input type="range" class="volume-slider" id="volume-slider" min="0" max="100" value="70">
                            <i class="fas fa-volume-up"></i>
                            <div class="volume-display" id="volume-display">70%</div>
                        </div>
                        <button class="sound-test-btn" id="test-sound-btn">
                            <i class="fas fa-play"></i> Test Sound
                        </button>
                    </div>
                </div>
                
                <div class="control-group">
                    <label class="control-label">Push Notifications</label>
                    <div class="control-row">
                        <label class="toggle-switch">
                            <input type="checkbox" id="push-notifications-enabled">
                            <span class="slider"></span>
                        </label>
                        <span class="ms-2">Enable browser notifications</span>
                        <button class="sound-test-btn ms-2" id="request-permission-btn">
                            <i class="fas fa-bell"></i> Enable Notifications
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="row mb-4">
        <!-- Next Bell -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Next Bell</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="next-bell-name">Loading...</div>
                            <div class="text-xs text-muted" id="next-bell-time">--:--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bell fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Season -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Current Season</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="current-season">Loading...</div>
                            <div class="text-xs text-muted">Auto-switched schedule</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-thermometer-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Notifications -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Notifications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-notifications-count">0</div>
                            <div class="text-xs text-muted">System alerts</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Special Schedule Alert -->
    <div class="row mb-4" id="special-schedule-alert" style="display: none;">
        <div class="col-12">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i>
                <strong>Special Schedule Active:</strong> <span id="special-schedule-name"></span>
                <p class="mb-0 mt-2" id="special-schedule-description"></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <ul class="nav nav-tabs card-header-tabs" id="scheduleTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="current-schedule-tab" data-bs-toggle="tab" data-bs-target="#current-schedule" type="button" role="tab">
                                <i class="fas fa-clock"></i> Current Schedule
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="manage-timings-tab" data-bs-toggle="tab" data-bs-target="#manage-timings" type="button" role="tab">
                                <i class="fas fa-cog"></i> Manage Timings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                                <i class="fas fa-bell"></i> Notifications
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="special-schedules-tab" data-bs-toggle="tab" data-bs-target="#special-schedules" type="button" role="tab">
                                <i class="fas fa-calendar-alt"></i> Special Schedules
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="scheduleTabContent">
                        <!-- Current Schedule Tab -->
                        <div class="tab-pane fade show active" id="current-schedule" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-8">
                                    <h5 class="mb-3">Today's Bell Schedule</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="current-schedule-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Bell Name</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Dynamic content -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <h5 class="mb-3">Upcoming Events</h5>
                                    <div id="upcoming-events">
                                        <!-- Dynamic content -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manage Timings Tab -->
                        <div class="tab-pane fade" id="manage-timings" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Bell Timing Management</h5>
                                <div>
                                    <select class="form-select d-inline-block w-auto me-2" id="season-filter">
                                        <option value="winter">Winter Schedule</option>
                                        <option value="summer">Summer Schedule</option>
                                    </select>
                                    <button class="btn btn-primary btn-sm" onclick="addBellTiming()">
                                        <i class="fas fa-plus"></i> Add Timing
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="bell-timings-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order</th>
                                            <th>Time</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Season</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dynamic content -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Notifications Tab -->
                        <div class="tab-pane fade" id="notifications" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Notification Management</h5>
                                <button class="btn btn-primary btn-sm" onclick="addNotification()">
                                    <i class="fas fa-plus"></i> Add Notification
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="notifications-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Bell Timing</th>
                                            <th>Type</th>
                                            <th>Title</th>
                                            <th>Priority</th>
                                            <th>Target</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dynamic content -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Special Schedules Tab -->
                        <div class="tab-pane fade" id="special-schedules" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Special Schedule Management</h5>
                                <div>
                                    <button class="btn btn-success btn-sm me-2" onclick="createPredefinedSchedule('half_day')">
                                        <i class="fas fa-clock"></i> Half Day
                                    </button>
                                    <button class="btn btn-info btn-sm me-2" onclick="createPredefinedSchedule('exam_schedule')">
                                        <i class="fas fa-graduation-cap"></i> Exam Schedule
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="addSpecialSchedule()">
                                        <i class="fas fa-plus"></i> Custom Schedule
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="special-schedules-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Priority</th>
                                            <th>Applies To</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Dynamic content -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Area -->
<div id="notification-area" class="position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
    <!-- Dynamic notifications will appear here -->
</div>

@include('bell-schedule.modals')

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize dashboard
    initializeDashboard();
    
    // Start real-time updates
    startRealTimeUpdates();
    
    // Load initial data
    loadCurrentSchedule();
    loadBellTimings();
    loadNotifications();
    loadSpecialSchedules();
});

function initializeDashboard() {
    // Update current time
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    
    // Check for bell notifications
    checkBellNotifications();
    setInterval(checkBellNotifications, 30000); // Check every 30 seconds
}

function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const dateString = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    $('#current-time').text(timeString);
    $('#current-date').text(dateString);
}

function startRealTimeUpdates() {
    // Fetch dashboard data every minute
    setInterval(function() {
        $.get('/bell-schedule/dashboard')
            .done(function(response) {
                if (response.success) {
                    updateDashboardData(response.data);
                }
            });
    }, 60000);
}

function updateDashboardData(data) {
    // Update next bell information
    if (data.next_bell) {
        $('#next-bell-name').text(data.next_bell.name);
        $('#next-bell-time').text(data.next_bell.time);
    } else {
        $('#next-bell-name').text('No upcoming bells');
        $('#next-bell-time').text('--:--');
    }
    
    // Update current season
    $('#current-season').text(data.current_season.charAt(0).toUpperCase() + data.current_season.slice(1));
    
    // Update special schedule alert
    if (data.current_schedule && data.current_schedule.special_schedule) {
        const schedule = data.current_schedule.special_schedule;
        $('#special-schedule-name').text(schedule.name);
        $('#special-schedule-description').text(schedule.description || '');
        $('#special-schedule-alert').show();
    } else {
        $('#special-schedule-alert').hide();
    }
}

function checkBellNotifications() {
    $.get('/bell-schedule/check-notification')
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                
                // Update active notifications count
                $('#active-notifications-count').text(data.active_notifications.length);
                
                // Show bell notifications if any
                if (data.should_ring && data.bells_to_ring.length > 0) {
                    data.bells_to_ring.forEach(function(bell) {
                        showBellNotification(bell);
                    });
                }
                
                // Show other active notifications
                if (data.has_notifications) {
                    data.active_notifications.forEach(function(notification) {
                        showSystemNotification(notification);
                    });
                }
            }
        });
}

function showBellNotification(bell) {
    const notification = $(`
        <div class="alert alert-success alert-dismissible fade show bell-notification" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-bell fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">${bell.name}</h5>
                    <p class="mb-0">${bell.description || 'Bell time notification'}</p>
                    <small class="text-muted">${bell.time}</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('#notification-area').append(notification);
    
    // Auto-dismiss after 10 seconds
    setTimeout(function() {
        notification.alert('close');
    }, 10000);
    
    // Play bell sound if available
    playBellSound();
}

function showSystemNotification(notification) {
    const alertClass = getAlertClass(notification.priority);
    const notificationElement = $(`
        <div class="alert ${alertClass} alert-dismissible fade show system-notification" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    <strong>${notification.title}</strong>
                    <p class="mb-0">${notification.message}</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('#notification-area').append(notificationElement);
    
    // Auto-dismiss based on notification settings
    if (notification.auto_dismiss) {
        setTimeout(function() {
            notificationElement.alert('close');
        }, (notification.dismiss_after_seconds || 30) * 1000);
    }
}

function getAlertClass(priority) {
    switch (priority) {
        case 'urgent': return 'alert-danger';
        case 'high': return 'alert-warning';
        case 'medium': return 'alert-info';
        case 'low': return 'alert-secondary';
        default: return 'alert-info';
    }
}

function playBellSound() {
    // Create and play bell sound
    const audio = new Audio('/sounds/bell.mp3');
    audio.play().catch(function(error) {
        console.log('Could not play bell sound:', error);
    });
}

function loadCurrentSchedule() {
    $.get('/bell-schedule/current-schedule')
        .done(function(response) {
            if (response.success) {
                updateCurrentScheduleTable(response.data.effective_schedule);
                updateUpcomingEvents(response.data.upcoming_notifications);
            }
        });
}

function loadBellTimings() {
    const season = $('#season-filter').val() || 'winter';
    $.get('/bell-timings', { season: season })
        .done(function(response) {
            if (response.success) {
                updateBellTimingsTable(response.data);
            }
        });
}

function updateBellTimingsTable(timings) {
    const tbody = $('#bell-timings-table tbody');
    tbody.empty();
    
    if (timings && timings.length > 0) {
        timings.forEach(function(timing, index) {
            const row = $(`
                <tr>
                    <td>${index + 1}</td>
                    <td>${timing.time}</td>
                    <td>${timing.name}</td>
                    <td><span class="badge bg-${getTypeColor(timing.type)}">${timing.type}</span></td>
                    <td><span class="badge bg-info">${timing.season}</span></td>
                    <td><span class="badge bg-${timing.is_active ? 'success' : 'secondary'}">${timing.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editBellTiming(${timing.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteBellTiming(${timing.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="7" class="text-center text-muted">No bell timings found</td></tr>');
    }
}

function loadNotifications() {
    $.get('/bell-notifications')
        .done(function(response) {
            if (response.success) {
                updateNotificationsTable(response.data);
            }
        });
}

function updateNotificationsTable(notifications) {
    const tbody = $('#notifications-table tbody');
    tbody.empty();
    
    if (notifications && notifications.length > 0) {
        notifications.forEach(function(notification) {
            const row = $(`
                <tr>
                    <td>${notification.bell_timing ? notification.bell_timing.name : 'N/A'}</td>
                    <td><span class="badge bg-info">${notification.type}</span></td>
                    <td>${notification.title}</td>
                    <td><span class="badge bg-${getPriorityColor(notification.priority)}">${notification.priority}</span></td>
                    <td>${notification.target_audience}</td>
                    <td><span class="badge bg-${notification.is_enabled ? 'success' : 'secondary'}">${notification.is_enabled ? 'Enabled' : 'Disabled'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editNotification(${notification.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-${notification.is_enabled ? 'warning' : 'success'} me-1" onclick="toggleNotification(${notification.id})">
                            <i class="fas fa-${notification.is_enabled ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification(${notification.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="7" class="text-center text-muted">No notifications found</td></tr>');
    }
}

function loadSpecialSchedules() {
    $.get('/special-schedules')
        .done(function(response) {
            if (response.success) {
                updateSpecialSchedulesTable(response.data);
            }
        });
}

function updateSpecialSchedulesTable(schedules) {
    const tbody = $('#special-schedules-table tbody');
    tbody.empty();
    
    if (schedules && schedules.length > 0) {
        schedules.forEach(function(schedule) {
            const row = $(`
                <tr>
                    <td>${schedule.date}</td>
                    <td>${schedule.name}</td>
                    <td><span class="badge bg-info">${schedule.type}</span></td>
                    <td><span class="badge bg-${getPriorityColor(schedule.priority)}">${schedule.priority}</span></td>
                    <td>${schedule.applies_to}</td>
                    <td><span class="badge bg-${schedule.is_active ? 'success' : 'secondary'}">${schedule.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editSpecialSchedule(${schedule.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-${schedule.is_active ? 'warning' : 'success'} me-1" onclick="toggleSpecialSchedule(${schedule.id})">
                            <i class="fas fa-${schedule.is_active ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSpecialSchedule(${schedule.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="7" class="text-center text-muted">No special schedules found</td></tr>');
    }
}

function getPriorityColor(priority) {
    switch (priority) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        case 'medium': return 'info';
        case 'low': return 'secondary';
        default: return 'secondary';
    }
}

function updateUpcomingEvents(events) {
    const container = $('#upcoming-events');
    container.empty();
    
    if (events && events.length > 0) {
        events.forEach(function(event) {
            const eventCard = $(`
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <h6 class="card-title mb-1">${event.title}</h6>
                        <p class="card-text small text-muted mb-1">${event.message}</p>
                        <small class="text-muted">${event.time}</small>
                    </div>
                </div>
            `);
            container.append(eventCard);
        });
    } else {
        container.append('<p class="text-muted">No upcoming events</p>');
    }
}

// Edit functions
function editBellTiming(id) {
    $.get(`/bell-timings/${id}`)
        .done(function(response) {
            if (response.success) {
                const timing = response.data;
                // Populate edit modal with timing data
                $('#edit-timing-id').val(timing.id);
                $('#edit-timing-name').val(timing.name);
                $('#edit-timing-time').val(timing.time);
                $('#edit-timing-type').val(timing.type);
                $('#edit-timing-season').val(timing.season);
                $('#editBellTimingModal').modal('show');
            }
        });
}

function editNotification(id) {
    $.get(`/bell-notifications/${id}`)
        .done(function(response) {
            if (response.success) {
                const notification = response.data;
                // Populate edit modal with notification data
                $('#edit-notification-id').val(notification.id);
                $('#edit-notification-title').val(notification.title);
                $('#edit-notification-message').val(notification.message);
                $('#edit-notification-type').val(notification.type);
                $('#edit-notification-priority').val(notification.priority);
                $('#editNotificationModal').modal('show');
            }
        });
}

function editSpecialSchedule(id) {
    $.get(`/special-schedules/${id}`)
        .done(function(response) {
            if (response.success) {
                const schedule = response.data;
                // Populate edit modal with schedule data
                $('#edit-schedule-id').val(schedule.id);
                $('#edit-schedule-name').val(schedule.name);
                $('#edit-schedule-date').val(schedule.date);
                $('#edit-schedule-type').val(schedule.type);
                $('#edit-schedule-description').val(schedule.description);
                $('#editSpecialScheduleModal').modal('show');
            }
        });
}

// Delete functions
function deleteBellTiming(id) {
    if (confirm('Are you sure you want to delete this bell timing?')) {
        $.ajax({
            url: `/bell-timings/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        }).done(function(response) {
            if (response.success) {
                loadBellTimings();
                loadCurrentSchedule();
                showAlert('Bell timing deleted successfully', 'success');
            } else {
                showAlert('Error deleting bell timing', 'danger');
            }
        });
    }
}

function deleteNotification(id) {
    if (confirm('Are you sure you want to delete this notification?')) {
        $.ajax({
            url: `/bell-notifications/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        }).done(function(response) {
            if (response.success) {
                loadNotifications();
                showAlert('Notification deleted successfully', 'success');
            } else {
                showAlert('Error deleting notification', 'danger');
            }
        });
    }
}

function deleteSpecialSchedule(id) {
    if (confirm('Are you sure you want to delete this special schedule?')) {
        $.ajax({
            url: `/special-schedules/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        }).done(function(response) {
            if (response.success) {
                loadSpecialSchedules();
                loadCurrentSchedule();
                showAlert('Special schedule deleted successfully', 'success');
            } else {
                showAlert('Error deleting special schedule', 'danger');
            }
        });
    }
}

// Toggle functions
function toggleNotification(id) {
    $.post(`/bell-notifications/${id}/toggle`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    }).done(function(response) {
        if (response.success) {
            loadNotifications();
            showAlert('Notification status updated', 'success');
        } else {
            showAlert('Error updating notification status', 'danger');
        }
    });
}

function toggleSpecialSchedule(id) {
    $.post(`/special-schedules/${id}/toggle`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    }).done(function(response) {
        if (response.success) {
            loadSpecialSchedules();
            loadCurrentSchedule();
            showAlert('Special schedule status updated', 'success');
        } else {
            showAlert('Error updating special schedule status', 'danger');
        }
    });
}

// Utility function to show alerts
function showAlert(message, type) {
    const alert = $(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('#notification-area').append(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        alert.alert('close');
    }, 5000);
}

// Season filter change handler
$('#season-filter').on('change', function() {
    loadBellTimings();
});

// View bell timing function
function viewBellTiming(id) {
    $.get(`/bell-timings/${id}`)
        .done(function(response) {
            if (response.success) {
                const timing = response.data;
                const modal = $(`
                    <div class="modal fade" id="viewBellTimingModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Bell Timing Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Name:</dt>
                                        <dd class="col-sm-9">${timing.name}</dd>
                                        <dt class="col-sm-3">Time:</dt>
                                        <dd class="col-sm-9">${timing.time}</dd>
                                        <dt class="col-sm-3">Type:</dt>
                                        <dd class="col-sm-9"><span class="badge bg-${getTypeColor(timing.type)}">${timing.type}</span></dd>
                                        <dt class="col-sm-3">Season:</dt>
                                        <dd class="col-sm-9"><span class="badge bg-info">${timing.season}</span></dd>
                                        <dt class="col-sm-3">Status:</dt>
                                        <dd class="col-sm-9"><span class="badge bg-${timing.is_active ? 'success' : 'secondary'}">${timing.is_active ? 'Active' : 'Inactive'}</span></dd>
                                    </dl>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
                $('body').append(modal);
                modal.modal('show');
                
                // Remove modal from DOM when hidden
                modal.on('hidden.bs.modal', function() {
                    modal.remove();
                });
            }
        });
}

function updateCurrentScheduleTable(schedule) {
    const tbody = $('#current-schedule-table tbody');
    tbody.empty();
    
    if (schedule && schedule.bell_timings) {
        schedule.bell_timings.forEach(function(timing) {
            const row = $(`
                <tr>
                    <td>${timing.time}</td>
                    <td>${timing.name}</td>
                    <td><span class="badge bg-${getTypeColor(timing.type)}">${timing.type}</span></td>
                    <td><span class="badge bg-${timing.is_active ? 'success' : 'secondary'}">${timing.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewBellTiming(${timing.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `);
            tbody.append(row);
        });
    } else {
        tbody.append('<tr><td colspan="5" class="text-center text-muted">No schedule available</td></tr>');
    }
}

function getTypeColor(type) {
    switch (type) {
        case 'start': return 'success';
        case 'end': return 'danger';
        case 'break': return 'warning';
        default: return 'secondary';
    }
}

// Additional functions for managing bell timings, notifications, and special schedules
// These would be implemented based on the specific UI requirements

function addBellTiming() {
    // Implementation for adding bell timing
    $('#addBellTimingModal').modal('show');
}

function addNotification() {
    // Implementation for adding notification
    $('#addNotificationModal').modal('show');
}

function addSpecialSchedule() {
    // Implementation for adding special schedule
    $('#specialScheduleModal').modal('show');
}

function createPredefinedSchedule(type) {
    // Implementation for creating predefined schedules
    const date = prompt('Enter date for ' + type.replace('_', ' ') + ' (YYYY-MM-DD):');
    if (date) {
        $.post('/special-schedules/create-predefined', {
            type: type,
            date: date,
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function(response) {
            if (response.success) {
                alert('Predefined schedule created successfully!');
                loadSpecialSchedules();
            }
        });
    }
}
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/sound-manager.js') }}"></script>
<script src="{{ asset('js/bell-notifications.js') }}"></script>
<script src="{{ asset('js/mobile-notification-service.js') }}"></script>
<script>
// Initialize enhanced bell timing system
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sound manager
    window.soundManager = new SoundManager();
    
    // Initialize bell notification system
    window.bellNotificationSystem = new BellNotificationSystem();
    
    // Initialize mobile notification service
    window.mobileNotificationService = new MobileNotificationService({
        apiEndpoint: '/api/bell-timings/schedule/enhanced',
        pushEndpoint: '/api/push-notifications',
        updateInterval: 30000, // 30 seconds
        enableVibration: true,
        enableSound: true
    });
    
    // Setup sound controls
    setupSoundControls();
    
    // Setup push notification controls
    setupPushNotificationControls();
    
    // Start real-time updates
    startRealTimeUpdates();
});

function setupSoundControls() {
    const soundToggle = document.getElementById('sound-enabled');
    const volumeSlider = document.getElementById('volume-slider');
    const volumeDisplay = document.getElementById('volume-display');
    const testSoundBtn = document.getElementById('test-sound-btn');
    
    // Sound toggle
    soundToggle.addEventListener('change', function() {
        window.soundManager.setSoundEnabled(this.checked);
        localStorage.setItem('bell-sound-enabled', this.checked);
    });
    
    // Volume control
    volumeSlider.addEventListener('input', function() {
        const volume = parseInt(this.value);
        window.soundManager.setVolume(volume);
        volumeDisplay.textContent = volume + '%';
        localStorage.setItem('bell-volume', volume);
    });
    
    // Test sound
    testSoundBtn.addEventListener('click', function() {
        window.soundManager.playBellSound();
    });
    
    // Load saved preferences
    const savedSoundEnabled = localStorage.getItem('bell-sound-enabled');
    if (savedSoundEnabled !== null) {
        soundToggle.checked = savedSoundEnabled === 'true';
        window.soundManager.setSoundEnabled(soundToggle.checked);
    }
    
    const savedVolume = localStorage.getItem('bell-volume');
    if (savedVolume) {
        volumeSlider.value = savedVolume;
        volumeDisplay.textContent = savedVolume + '%';
        window.soundManager.setVolume(parseInt(savedVolume));
    }
}

function setupPushNotificationControls() {
    const pushToggle = document.getElementById('push-notifications-enabled');
    const requestPermissionBtn = document.getElementById('request-permission-btn');
    
    // Check current permission status
    if ('Notification' in window) {
        pushToggle.checked = Notification.permission === 'granted';
        requestPermissionBtn.style.display = Notification.permission === 'granted' ? 'none' : 'inline-block';
    }
    
    // Push notification toggle
    pushToggle.addEventListener('change', function() {
        if (this.checked && Notification.permission !== 'granted') {
            this.checked = false;
            requestNotificationPermission();
        } else {
            window.bellNotificationSystem.setPushNotificationsEnabled(this.checked);
        }
    });
    
    // Request permission button
    requestPermissionBtn.addEventListener('click', requestNotificationPermission);
}

function requestNotificationPermission() {
    if ('Notification' in window) {
        Notification.requestPermission().then(function(permission) {
            const pushToggle = document.getElementById('push-notifications-enabled');
            const requestPermissionBtn = document.getElementById('request-permission-btn');
            
            if (permission === 'granted') {
                pushToggle.checked = true;
                requestPermissionBtn.style.display = 'none';
                window.bellNotificationSystem.setPushNotificationsEnabled(true);
                
                // Show success notification
                new Notification('Bell Timing System', {
                    body: 'Push notifications enabled successfully!',
                    icon: '/favicon.ico'
                });
            } else {
                pushToggle.checked = false;
                alert('Please enable notifications in your browser settings to receive bell alerts.');
            }
        });
    }
}

function startRealTimeUpdates() {
     // Update clock and schedule every second
     setInterval(updateRealTimeClock, 1000);
     
     // Update schedule data every minute
     setInterval(loadEnhancedSchedule, 60000);
     
     // Initial load
     updateRealTimeClock();
     loadEnhancedSchedule();
 }
 
 function loadEnhancedSchedule() {
     fetch('/bell-schedule/enhanced-schedule')
         .then(response => response.json())
         .then(data => {
             if (data.success) {
                 updateEnhancedDisplay(data.data);
             }
         })
         .catch(error => {
             console.error('Error loading enhanced schedule:', error);
         });
 }
 
 function updateEnhancedDisplay(scheduleData) {
     // Update season indicator
     const seasonDisplay = document.getElementById('current-season-display');
     if (seasonDisplay) {
         seasonDisplay.textContent = scheduleData.current_season.charAt(0).toUpperCase() + scheduleData.current_season.slice(1) + ' Schedule';
     }
     
     // Update current period
     const currentPeriodName = document.getElementById('current-period-name');
     const currentPeriodTime = document.getElementById('current-period-time');
     const periodProgressBar = document.getElementById('period-progress-bar');
     
     if (scheduleData.current_period) {
         if (currentPeriodName) {
             currentPeriodName.textContent = scheduleData.current_period.name;
         }
         if (currentPeriodTime) {
             currentPeriodTime.textContent = scheduleData.current_period.time;
         }
         if (periodProgressBar) {
             periodProgressBar.style.width = scheduleData.current_period.progress + '%';
         }
     } else {
         if (currentPeriodName) {
             currentPeriodName.textContent = 'No Current Period';
         }
         if (currentPeriodTime) {
             currentPeriodTime.textContent = '--:--';
         }
         if (periodProgressBar) {
             periodProgressBar.style.width = '0%';
         }
     }
     
     // Update next period
     const nextPeriodName = document.getElementById('next-period-name');
     const timeRemaining = document.getElementById('time-remaining');
     
     if (scheduleData.next_period) {
         if (nextPeriodName) {
             nextPeriodName.textContent = scheduleData.next_period.name;
         }
         if (timeRemaining) {
             const minutes = scheduleData.next_period.time_remaining_minutes;
             const hours = Math.floor(minutes / 60);
             const mins = minutes % 60;
             timeRemaining.textContent = hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
         }
     } else {
         if (nextPeriodName) {
             nextPeriodName.textContent = 'No Upcoming Period';
         }
         if (timeRemaining) {
             timeRemaining.textContent = '--:--';
         }
     }
     
     // Update legacy displays for compatibility
     updateLegacyDisplays(scheduleData);
 }
 
 function updateLegacyDisplays(scheduleData) {
     // Update next bell card
     const nextBellName = document.getElementById('next-bell-name');
     const nextBellTime = document.getElementById('next-bell-time');
     
     if (scheduleData.next_period) {
         if (nextBellName) {
             nextBellName.textContent = scheduleData.next_period.name;
         }
         if (nextBellTime) {
             nextBellTime.textContent = scheduleData.next_period.time;
         }
     }
     
     // Update current season card
     const currentSeason = document.getElementById('current-season');
     if (currentSeason) {
         currentSeason.textContent = scheduleData.current_season.charAt(0).toUpperCase() + scheduleData.current_season.slice(1);
     }
     
     // Update active notifications count
     const activeNotificationsCount = document.getElementById('active-notifications-count');
     if (activeNotificationsCount) {
         activeNotificationsCount.textContent = scheduleData.upcoming_notifications ? scheduleData.upcoming_notifications.length : 0;
     }
 }

function updateRealTimeClock() {
    const now = new Date();
    
    // Update enhanced clock display
    const timeElement = document.getElementById('enhanced-current-time');
    const dateElement = document.getElementById('enhanced-current-date');
    
    if (timeElement) {
        timeElement.textContent = now.toLocaleTimeString('en-US', {
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }
    
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    // Update legacy clock display for compatibility
    const legacyTimeElement = document.getElementById('current-time');
    const legacyDateElement = document.getElementById('current-date');
    
    if (legacyTimeElement) {
        legacyTimeElement.textContent = now.toLocaleTimeString('en-US', {
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }
    
    if (legacyDateElement) {
        legacyDateElement.textContent = now.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        });
    }
}
</script>
@endpush

@section('styles')
<style>
.bell-notification {
    min-width: 350px;
    margin-bottom: 10px;
}

.system-notification {
    min-width: 300px;
    margin-bottom: 10px;
}

.card-header-tabs .nav-link {
    border: none;
    color: #6c757d;
}

.card-header-tabs .nav-link.active {
    background-color: transparent;
    border-bottom: 2px solid #007bff;
    color: #007bff;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
}

#notification-area {
    max-width: 400px;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
</style>
@endsection