@extends('layouts.app')

@section('title', 'Bell Schedule - Timetable')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Bell Schedule - Timetable</h4>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" onclick="refreshSchedule()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                            <i class="fas fa-plus"></i> Add Period
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Current Status -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5>Current Time</h5>
                                    <h3 id="currentTime">{{ date('H:i:s') }}</h3>
                                    <small id="currentDate">{{ date('Y-m-d') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>Current Period</h5>
                                    <h3 id="currentPeriod">{{ $currentPeriod['name'] ?? 'No Period' }}</h3>
                                    <small id="currentPeriodTime">
                                        {{ isset($currentPeriod) ? $currentPeriod['start_time'] . ' - ' . $currentPeriod['end_time'] : 'N/A' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5>Next Period</h5>
                                    <h3 id="nextPeriod">{{ $nextPeriod['name'] ?? 'No Period' }}</h3>
                                    <small id="nextPeriodTime">
                                        {{ isset($nextPeriod) ? $nextPeriod['start_time'] . ' - ' . $nextPeriod['end_time'] : 'N/A' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5>Active Season</h5>
                                    <h3 id="activeSeason">{{ $activeSeason ?? 'Not Set' }}</h3>
                                    <small>{{ $seasonStartDate ?? 'N/A' }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Filter -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form id="scheduleFilter" class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_season">Season</label>
                                        <select class="form-control" id="filter_season" name="season">
                                            <option value="">All Seasons</option>
                                            <option value="winter" {{ request('season') == 'winter' ? 'selected' : '' }}>Winter</option>
                                            <option value="summer" {{ request('season') == 'summer' ? 'selected' : '' }}>Summer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_date">Date</label>
                                        <input type="date" class="form-control" id="filter_date" name="date" 
                                               value="{{ request('date', date('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_status">Status</label>
                                        <select class="form-control" id="filter_status" name="status">
                                            <option value="">All</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary form-control">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Schedule Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Daily Schedule</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="scheduleTable">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>Duration</th>
                                            <th>Season</th>
                                            <th>Status</th>
                                            <th>Progress</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($schedule ?? [] as $period)
                                        <tr class="{{ $period->is_current ? 'table-success' : ($period->is_next ? 'table-info' : '') }}">
                                            <td>
                                                <strong>{{ $period->period_name }}</strong>
                                                @if($period->is_current)
                                                    <span class="badge badge-success ml-2">Current</span>
                                                @elseif($period->is_next)
                                                    <span class="badge badge-info ml-2">Next</span>
                                                @endif
                                            </td>
                                            <td>{{ $period->start_time }}</td>
                                            <td>{{ $period->end_time }}</td>
                                            <td>{{ $period->duration }} min</td>
                                            <td>
                                                <span class="badge badge-{{ $period->season == 'winter' ? 'info' : 'warning' }}">
                                                    {{ ucfirst($period->season) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $period->is_active ? 'success' : 'secondary' }}">
                                                    {{ $period->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($period->is_current)
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                             role="progressbar" style="width: {{ $period->progress }}%">
                                                            {{ $period->progress }}%
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">{{ $period->status_text }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info" onclick="viewPeriodDetails({{ $period->id }})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-warning" onclick="editPeriod({{ $period->id }})">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-{{ $period->is_active ? 'secondary' : 'success' }}" 
                                                            onclick="togglePeriod({{ $period->id }})">
                                                        <i class="fas fa-{{ $period->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No schedule found for the selected criteria</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Total Periods</h5>
                                    <h3 class="text-primary">{{ $stats['total_periods'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Active Periods</h5>
                                    <h3 class="text-success">{{ $stats['active_periods'] ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Total Duration</h5>
                                    <h3 class="text-info">{{ $stats['total_duration'] ?? 0 }} min</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Remaining Time</h5>
                                    <h3 class="text-warning" id="remainingTime">{{ $stats['remaining_time'] ?? 0 }} min</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Period Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Period</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="periodForm">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="period_id" name="id">
                    <div class="form-group">
                        <label for="period_name">Period Name</label>
                        <input type="text" class="form-control" id="period_name" name="period_name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_time">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_time">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="season">Season</label>
                        <select class="form-control" id="season" name="season" required>
                            <option value="">Select Season</option>
                            <option value="winter">Winter</option>
                            <option value="summer">Summer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active Period
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Period</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Update current time every second
    setInterval(updateCurrentTime, 1000);
    
    // Auto-refresh schedule every 30 seconds
    setInterval(refreshSchedule, 30000);

    // Schedule filter form
    $('#scheduleFilter').on('submit', function(e) {
        e.preventDefault();
        const params = new URLSearchParams(new FormData(this));
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    });

    // Period form submission
    $('#periodForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const isEdit = $('#period_id').val();
        const url = isEdit ? 
            `{{ route("bell-timings.update", ":id") }}`.replace(':id', isEdit) :
            '{{ route("bell-timings.store") }}';
        
        $.ajax({
            url: url,
            method: isEdit ? 'PUT' : 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#scheduleModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Error saving period');
            }
        });
    });
});

function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString();
    const dateString = now.toLocaleDateString();
    
    $('#currentTime').text(timeString);
    $('#currentDate').text(dateString);
}

function refreshSchedule() {
    $.ajax({
        url: '{{ route("bell-timings.get-active-schedule") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                // Update current period info
                if (response.data.current_period) {
                    $('#currentPeriod').text(response.data.current_period.period_name);
                    $('#currentPeriodTime').text(
                        response.data.current_period.start_time + ' - ' + 
                        response.data.current_period.end_time
                    );
                }
                
                // Update next period info
                if (response.data.next_period) {
                    $('#nextPeriod').text(response.data.next_period.period_name);
                    $('#nextPeriodTime').text(
                        response.data.next_period.start_time + ' - ' + 
                        response.data.next_period.end_time
                    );
                }
                
                // Update remaining time
                if (response.data.remaining_time) {
                    $('#remainingTime').text(response.data.remaining_time + ' min');
                }
            }
        },
        error: function(xhr) {
            console.error('Error refreshing schedule');
        }
    });
}

function viewPeriodDetails(periodId) {
    // Implementation for viewing period details
    window.location.href = `/bell-timings/${periodId}`;
}

function editPeriod(periodId) {
    $.ajax({
        url: `/bell-timings/${periodId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const period = response.data;
                $('#period_id').val(period.id);
                $('#period_name').val(period.period_name);
                $('#start_time').val(period.start_time);
                $('#end_time').val(period.end_time);
                $('#season').val(period.season);
                $('#is_active').prop('checked', period.is_active);
                
                $('.modal-title').text('Edit Period');
                $('#scheduleModal').modal('show');
            }
        },
        error: function(xhr) {
            toastr.error('Error loading period details');
        }
    });
}

function togglePeriod(periodId) {
    $.ajax({
        url: `/bell-timings/${periodId}/toggle`,
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
            toastr.error('Error toggling period');
        }
    });
}
</script>
@endsection