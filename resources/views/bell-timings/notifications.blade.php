@extends('layouts.app')

@section('title', 'Bell Timing Notifications')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Bell Timing Notifications & Alerts</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#notificationModal">
                        <i class="fas fa-plus"></i> Add Notification
                    </button>
                </div>
                <div class="card-body">
                    <!-- Notification Settings -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Global Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form id="globalSettingsForm">
                                        @csrf
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="enable_notifications" 
                                                       name="enable_notifications" {{ $settings['enable_notifications'] ?? false ? 'checked' : '' }}>
                                                <label class="form-check-label" for="enable_notifications">
                                                    Enable Bell Notifications
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="enable_sound" 
                                                       name="enable_sound" {{ $settings['enable_sound'] ?? false ? 'checked' : '' }}>
                                                <label class="form-check-label" for="enable_sound">
                                                    Enable Sound Alerts
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="default_volume">Default Volume (%)</label>
                                            <input type="range" class="form-control-range" id="default_volume" 
                                                   name="default_volume" min="0" max="100" 
                                                   value="{{ $settings['default_volume'] ?? 80 }}">
                                            <small class="form-text text-muted">Current: <span id="volumeValue">{{ $settings['default_volume'] ?? 80 }}</span>%</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="notification_duration">Notification Duration (seconds)</label>
                                            <input type="number" class="form-control" id="notification_duration" 
                                                   name="notification_duration" min="1" max="60" 
                                                   value="{{ $settings['notification_duration'] ?? 5 }}">
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Save Settings
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Test Notifications</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="test_sound">Test Sound</label>
                                        <select class="form-control" id="test_sound">
                                            <option value="bell.mp3">Default Bell</option>
                                            <option value="chime.mp3">Chime</option>
                                            <option value="buzzer.mp3">Buzzer</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-info" onclick="testNotification()">
                                        <i class="fas fa-play"></i> Test Notification
                                    </button>
                                    <button type="button" class="btn btn-warning" onclick="testSound()">
                                        <i class="fas fa-volume-up"></i> Test Sound
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Rules -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Notification Rules</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="notificationRulesTable">
                                    <thead>
                                        <tr>
                                            <th>Rule Name</th>
                                            <th>Trigger</th>
                                            <th>Recipients</th>
                                            <th>Sound</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($notificationRules ?? [] as $rule)
                                        <tr>
                                            <td>{{ $rule->name }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ $rule->trigger_type }}</span>
                                                <small class="d-block">{{ $rule->trigger_condition }}</small>
                                            </td>
                                            <td>
                                                @foreach($rule->recipients as $recipient)
                                                    <span class="badge badge-secondary">{{ $recipient }}</span>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if($rule->sound_enabled)
                                                    <i class="fas fa-volume-up text-success"></i>
                                                    <small>{{ $rule->sound_file }}</small>
                                                @else
                                                    <i class="fas fa-volume-mute text-muted"></i>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $rule->is_active ? 'success' : 'secondary' }}">
                                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="editRule({{ $rule->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-{{ $rule->is_active ? 'warning' : 'success' }}" 
                                                        onclick="toggleRule({{ $rule->id }})">
                                                    <i class="fas fa-{{ $rule->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteRule({{ $rule->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No notification rules configured</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Notifications -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Notifications</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Type</th>
                                            <th>Message</th>
                                            <th>Recipients</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentNotifications ?? [] as $notification)
                                        <tr>
                                            <td>{{ $notification->created_at->format('H:i:s') }}</td>
                                            <td>
                                                <span class="badge badge-{{ $notification->type == 'bell' ? 'primary' : 'info' }}">
                                                    {{ ucfirst($notification->type) }}
                                                </span>
                                            </td>
                                            <td>{{ $notification->message }}</td>
                                            <td>{{ $notification->recipient_count }} recipients</td>
                                            <td>
                                                <span class="badge badge-{{ $notification->status == 'sent' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($notification->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No recent notifications</td>
                                        </tr>
                                        @endforelse
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

<!-- Notification Rule Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Notification Rule</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="notificationRuleForm">
                <div class="modal-body">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rule_name">Rule Name</label>
                                <input type="text" class="form-control" id="rule_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="trigger_type">Trigger Type</label>
                                <select class="form-control" id="trigger_type" name="trigger_type" required>
                                    <option value="">Select Trigger</option>
                                    <option value="period_start">Period Start</option>
                                    <option value="period_end">Period End</option>
                                    <option value="break_start">Break Start</option>
                                    <option value="break_end">Break End</option>
                                    <option value="custom_time">Custom Time</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="trigger_condition">Trigger Condition</label>
                        <input type="text" class="form-control" id="trigger_condition" name="trigger_condition" 
                               placeholder="e.g., 5 minutes before, at exact time">
                    </div>
                    <div class="form-group">
                        <label for="message_template">Message Template</label>
                        <textarea class="form-control" id="message_template" name="message_template" rows="3" 
                                  placeholder="Notification message template"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recipients">Recipients</label>
                                <select class="form-control" id="recipients" name="recipients[]" multiple>
                                    <option value="all_teachers">All Teachers</option>
                                    <option value="all_students">All Students</option>
                                    <option value="admin_staff">Admin Staff</option>
                                    <option value="specific_classes">Specific Classes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sound_file">Sound File</label>
                                <select class="form-control" id="sound_file" name="sound_file">
                                    <option value="">No Sound</option>
                                    <option value="bell.mp3">Default Bell</option>
                                    <option value="chime.mp3">Chime</option>
                                    <option value="buzzer.mp3">Buzzer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active Rule
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Volume slider update
    $('#default_volume').on('input', function() {
        $('#volumeValue').text($(this).val());
    });

    // Global settings form
    $('#globalSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("bell-timings.update-notifications") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Error updating settings');
            }
        });
    });

    // Notification rule form
    $('#notificationRuleForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("bell-timings.store-notification-rule") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#notificationModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Error saving notification rule');
            }
        });
    });
});

function testNotification() {
    $.ajax({
        url: '{{ route("bell-timings.test-notification") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            type: 'test'
        },
        success: function(response) {
            toastr.info('Test notification sent');
        },
        error: function(xhr) {
            toastr.error('Error sending test notification');
        }
    });
}

function testSound() {
    const soundFile = $('#test_sound').val();
    const audio = new Audio(`/sounds/${soundFile}`);
    const volume = $('#default_volume').val() / 100;
    audio.volume = volume;
    audio.play().catch(e => {
        toastr.error('Error playing sound');
    });
}

function editRule(ruleId) {
    // Implementation for editing notification rule
    window.location.href = `/bell-timings/notifications/edit/${ruleId}`;
}

function toggleRule(ruleId) {
    $.ajax({
        url: `/bell-timings/notifications/toggle/${ruleId}`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error('Error toggling rule');
        }
    });
}

function deleteRule(ruleId) {
    if (confirm('Are you sure you want to delete this notification rule?')) {
        $.ajax({
            url: `/bell-timings/notifications/delete/${ruleId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Error deleting rule');
            }
        });
    }
}
</script>
@endsection