@extends('layouts.app')

@section('title', 'Season Management - Bell Timings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Season Management</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#seasonModal">
                        <i class="fas fa-plus"></i> Add Season
                    </button>
                </div>
                <div class="card-body">
                    <!-- Current Season Status -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Current Season</h5>
                                    <h3 id="currentSeason">{{ $currentSeason ?? 'Not Set' }}</h3>
                                    <p class="mb-0">Active since: <span id="activeSince">{{ $activeSince ?? 'N/A' }}</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Next Season Change</h5>
                                    <h3 id="nextSeason">{{ $nextSeason ?? 'Not Scheduled' }}</h3>
                                    <p class="mb-0">Effective: <span id="nextDate">{{ $nextDate ?? 'N/A' }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Season Switch Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Switch Season</h5>
                        </div>
                        <div class="card-body">
                            <form id="seasonSwitchForm">
                                @csrf
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="season">Season</label>
                                            <select class="form-control" id="season" name="season" required>
                                                <option value="">Select Season</option>
                                                <option value="winter">Winter</option>
                                                <option value="summer">Summer</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="effective_date">Effective Date</label>
                                            <input type="date" class="form-control" id="effective_date" name="effective_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-success form-control">
                                                <i class="fas fa-sync-alt"></i> Switch Season
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Season History -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Season History</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="seasonHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>Season</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Duration</th>
                                            <th>Changed By</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($seasonHistory ?? [] as $history)
                                        <tr>
                                            <td>
                                                <span class="badge badge-{{ $history->season == 'winter' ? 'info' : 'warning' }}">
                                                    {{ ucfirst($history->season) }}
                                                </span>
                                            </td>
                                            <td>{{ $history->start_date }}</td>
                                            <td>{{ $history->end_date ?? 'Current' }}</td>
                                            <td>{{ $history->duration ?? 'Ongoing' }}</td>
                                            <td>{{ $history->changed_by }}</td>
                                            <td>
                                                <span class="badge badge-{{ $history->is_active ? 'success' : 'secondary' }}">
                                                    {{ $history->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="viewSeasonDetails({{ $history->id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No season history found</td>
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

<!-- Season Modal -->
<div class="modal fade" id="seasonModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Season</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addSeasonForm">
                <div class="modal-body">
                    @csrf
                    <div class="form-group">
                        <label for="modal_season">Season</label>
                        <select class="form-control" id="modal_season" name="season" required>
                            <option value="">Select Season</option>
                            <option value="winter">Winter</option>
                            <option value="summer">Summer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal_start_date">Start Date</label>
                        <input type="date" class="form-control" id="modal_start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_end_date">End Date (Optional)</label>
                        <input type="date" class="form-control" id="modal_end_date" name="end_date">
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="modal_is_active" name="is_active">
                            <label class="form-check-label" for="modal_is_active">
                                Set as Active Season
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Season</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Season switch form submission
    $('#seasonSwitchForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("bell-timings.update-season") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Error switching season');
                console.error(xhr.responseText);
            }
        });
    });

    // Add season form submission
    $('#addSeasonForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("bell-timings.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#seasonModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Error adding season');
                console.error(xhr.responseText);
            }
        });
    });

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    $('#effective_date, #modal_start_date').attr('min', today);
});

function viewSeasonDetails(seasonId) {
    // Implementation for viewing season details
    window.location.href = `/bell-timings/season/${seasonId}`;
}
</script>
@endsection