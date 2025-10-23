@extends('layouts.app')

@section('title','Transport Management')

@section('content')
<div class="container py-3">
    <div class="d-flex align-items-center mb-3">
        <h4 class="mb-0">Transport - Bus Tracking</h4>
        <span class="ms-2 badge bg-info">Live</span>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Bus</th>
                            <th>Number Plate</th>
                            <th>Driver</th>
                            <th>Route</th>
                            <th>Last Location</th>
                            <th>Recorded At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buses as $bus)
                            <tr>
                                <td>{{ $bus->name }}</td>
                                <td>{{ $bus->number_plate }}</td>
                                <td>{{ $bus->driver_name }} ({{ $bus->driver_phone }})</td>
                                <td>{{ $bus->route_name }}</td>
                                <td>
                                    @if($bus->latestLocation)
                                        {{ $bus->latestLocation->latitude }}, {{ $bus->latestLocation->longitude }}
                                    @else
                                        <span class="text-muted">No data</span>
                                    @endif
                                </td>
                                <td>{{ optional($bus->latestLocation)->recorded_at?->format('d M, H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No buses found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
