@extends('layouts.app')

@section('title', 'Audit Log Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>
                        <i class="fas fa-shield-alt mr-2"></i>
                        Audit Log Details
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.audit.index') }}">Audit Logs</a>
                            </li>
                            <li class="breadcrumb-item active">Log #{{ $auditLog->id }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.audit.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Back to Logs
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Main Details -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                Activity Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold">Log ID:</td>
                                            <td><span class="badge bg-secondary">#{{ $auditLog->id }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Event:</td>
                                            <td>
                                                <span class="badge {{ $auditLog->status_badge }}">
                                                    <i class="{{ $auditLog->event_icon }} mr-1"></i>
                                                    {{ ucfirst(str_replace('_', ' ', $auditLog->event)) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">User:</td>
                                            <td>
                                                @if($auditLog->user)
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm me-2">
                                                            <span class="avatar-initial rounded-circle bg-primary">
                                                                {{ substr($auditLog->user->name, 0, 1) }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold">{{ $auditLog->user->name }}</div>
                                                            <small class="text-muted">{{ $auditLog->user->email }}</small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">System</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Date/Time:</td>
                                            <td>
                                                <div>{{ $auditLog->created_at->format('F d, Y') }}</div>
                                                <div>{{ $auditLog->created_at->format('h:i:s A') }}</div>
                                                <small class="text-muted">{{ $auditLog->created_at->diffForHumans() }}</small>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="fw-bold">Model Type:</td>
                                            <td>
                                                @if($auditLog->auditable_type)
                                                    <code>{{ class_basename($auditLog->auditable_type) }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Model ID:</td>
                                            <td>
                                                @if($auditLog->auditable_id)
                                                    <code>{{ $auditLog->auditable_id }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">IP Address:</td>
                                            <td><code>{{ $auditLog->ip_address }}</code></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">URL:</td>
                                            <td>
                                                @if($auditLog->url)
                                                    <code class="text-break">{{ $auditLog->url }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if($auditLog->tags && count($auditLog->tags) > 0)
                                <div class="mt-3">
                                    <strong>Tags:</strong>
                                    <div class="mt-2">
                                        @foreach($auditLog->tags as $tag)
                                            <span class="badge bg-light text-dark me-1">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Changes Details -->
                    @if($auditLog->old_values || $auditLog->new_values)
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-exchange-alt mr-2"></i>
                                    Data Changes
                                </h5>
                            </div>
                            <div class="card-body">
                                @if($auditLog->formatted_changes && count($auditLog->formatted_changes) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Field</th>
                                                    <th>Old Value</th>
                                                    <th>New Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($auditLog->formatted_changes as $field => $changes)
                                                    <tr>
                                                        <td class="fw-bold">{{ ucfirst(str_replace('_', ' ', $field)) }}</td>
                                                        <td>
                                                            @if(isset($changes['old']))
                                                                @if(is_array($changes['old']))
                                                                    <pre class="bg-light p-2 rounded"><code>{{ json_encode($changes['old'], JSON_PRETTY_PRINT) }}</code></pre>
                                                                @else
                                                                    <span class="text-danger">{{ $changes['old'] ?: '(empty)' }}</span>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(isset($changes['new']))
                                                                @if(is_array($changes['new']))
                                                                    <pre class="bg-light p-2 rounded"><code>{{ json_encode($changes['new'], JSON_PRETTY_PRINT) }}</code></pre>
                                                                @else
                                                                    <span class="text-success">{{ $changes['new'] ?: '(empty)' }}</span>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        No field changes recorded for this activity.
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Raw Data -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-code mr-2"></i>
                                Raw Data
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if($auditLog->old_values)
                                    <div class="col-md-6">
                                        <h6>Old Values:</h6>
                                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT) }}</code></pre>
                                    </div>
                                @endif
                                
                                @if($auditLog->new_values)
                                    <div class="col-md-6">
                                        <h6>New Values:</h6>
                                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT) }}</code></pre>
                                    </div>
                                @endif
                                
                                @if(!$auditLog->old_values && !$auditLog->new_values)
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            No data values recorded for this activity.
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Related Model -->
                    @if($auditLog->auditable)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-link mr-2"></i>
                                    Related Model
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ class_basename($auditLog->auditable_type) }}</h6>
                                        <p class="text-muted mb-0">ID: {{ $auditLog->auditable_id }}</p>
                                        @if(method_exists($auditLog->auditable, 'name') && $auditLog->auditable->name)
                                            <p class="mb-0"><strong>{{ $auditLog->auditable->name }}</strong></p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- System Information -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-desktop mr-2"></i>
                                System Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="fw-bold">User Agent:</td>
                                </tr>
                                <tr>
                                    <td>
                                        <small class="text-muted text-break">
                                            {{ $auditLog->user_agent ?: 'Not recorded' }}
                                        </small>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Request Method:</td>
                                </tr>
                                <tr>
                                    <td>
                                        @if($auditLog->url)
                                            @php
                                                $method = 'GET'; // Default
                                                if (str_contains($auditLog->url, '/store') || str_contains($auditLog->url, '/create')) {
                                                    $method = 'POST';
                                                } elseif (str_contains($auditLog->url, '/update') || str_contains($auditLog->url, '/edit')) {
                                                    $method = 'PUT/PATCH';
                                                } elseif (str_contains($auditLog->url, '/delete') || str_contains($auditLog->url, '/destroy')) {
                                                    $method = 'DELETE';
                                                }
                                            @endphp
                                            <span class="badge bg-info">{{ $method }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Related Activities -->
                    @if($auditLog->user_id)
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history mr-2"></i>
                                    Recent Activities by User
                                </h5>
                            </div>
                            <div class="card-body">
                                @php
                                    $recentActivities = App\Models\AuditTrail::where('user_id', $auditLog->user_id)
                                        ->where('id', '!=', $auditLog->id)
                                        ->latest()
                                        ->limit(5)
                                        ->get();
                                @endphp
                                
                                @if($recentActivities->count() > 0)
                                    <div class="list-group list-group-flush">
                                        @foreach($recentActivities as $activity)
                                            <div class="list-group-item px-0 py-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <span class="badge {{ $activity->status_badge }} badge-sm">
                                                            {{ ucfirst(str_replace('_', ' ', $activity->event)) }}
                                                        </span>
                                                        @if($activity->auditable_type)
                                                            <div class="mt-1">
                                                                <small class="text-muted">
                                                                    {{ class_basename($activity->auditable_type) }}
                                                                    @if($activity->auditable_id)
                                                                        #{{ $activity->auditable_id }}
                                                                    @endif
                                                                </small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ $activity->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mt-3">
                                        <a href="{{ route('admin.audit.index', ['user_id' => $auditLog->user_id]) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            View All Activities
                                        </a>
                                    </div>
                                @else
                                    <div class="text-muted text-center py-3">
                                        <i class="fas fa-history fa-2x mb-2"></i>
                                        <p class="mb-0">No other recent activities</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection