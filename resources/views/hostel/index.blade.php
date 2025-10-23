@extends('layouts.app')

@section('title','Hostel Management')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Hostel Occupancy</h4>
        @if(session('status'))
            <span class="badge bg-success">{{ session('status') }}</span>
        @endif
    </div>

    @forelse($buildings as $building)
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $building->name }}</strong>
                    <small class="text-muted ms-2">Warden: {{ $building->warden_name ?? 'N/A' }}</small>
                </div>
                <span class="badge bg-secondary">{{ strtoupper($building->gender) }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Room</th>
                                <th>Beds</th>
                                <th>Occupied</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($building->rooms as $room)
                                <tr>
                                    <td>{{ $room->room_number }}</td>
                                    <td>{{ $room->bed_count }}</td>
                                    <td>{{ $room->activeAllocations->count() }}</td>
                                    <td>{{ ucfirst($room->status) }}</td>
                                    <td>
                                        <form action="{{ route('hostel.allocate', $room->id) }}" method="POST" class="d-flex gap-2">
                                            @csrf
                                            <input type="number" name="student_id" class="form-control form-control-sm" placeholder="Student ID" required>
                                            <button class="btn btn-sm btn-primary" @if($room->activeAllocations->count() >= $room->bed_count) disabled @endif>Allocate</button>
                                        </form>
                                        <div class="mt-2">
                                            @foreach($room->activeAllocations as $alloc)
                                                <form action="{{ route('hostel.vacate', $alloc->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-danger">Vacate (SID: {{ $alloc->student_id }})</button>
                                                </form>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">No rooms found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No hostel buildings found.</div>
    @endforelse
</div>
@endsection
