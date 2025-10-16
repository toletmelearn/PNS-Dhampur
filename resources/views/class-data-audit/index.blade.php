@extends('layouts.app')

@section('title', 'Class Data Audit')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Class Data Audit</h2>
    <div class="d-flex gap-2">
        @if(auth()->user()->hasPermission('view_audit_statistics'))
            <a href="{{ route('class-data-audit.analytics') }}" class="btn btn-outline-primary">
                <i class="fas fa-chart-line me-1"></i> Analytics
            </a>
        @endif
        @if(auth()->user()->hasPermission('export_audit_reports'))
            <form action="{{ route('class-data-audit.export') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="format" value="csv">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-file-export me-1"></i> Export CSV
                </button>
            </form>
        @endif
    </div>
    
    @php
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => route('dashboard')],
            ['title' => 'Class Data Audit', 'url' => route('class-data-audit.index')]
        ];
    @endphp
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>Total Audits</small>
                    <h3>{{ $statistics['total_audits'] ?? 0 }}</h3>
                </div>
                <i class="fas fa-clipboard-list fa-2x"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>Pending Approvals</small>
                    <h3>{{ $statistics['pending_approvals'] ?? 0 }}</h3>
                </div>
                <i class="fas fa-hourglass-half fa-2x"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>High Risk</small>
                    <h3>{{ $statistics['high_risk_changes'] ?? 0 }}</h3>
                </div>
                <i class="fas fa-triangle-exclamation fa-2x"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>Critical</small>
                    <h3>{{ $statistics['critical_changes'] ?? 0 }}</h3>
                </div>
                <i class="fas fa-skull-crossbones fa-2x"></i>
            </div>
        </div>
    </div>
    
    @if(session('success'))
        <div class="col-12">
            <div class="alert alert-success">{{ session('success') }}</div>
        </div>
    @endif
</div>

<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-filter me-2"></i>Filters</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('class-data-audit.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Auditable Type</label>
                <select name="auditable_type" class="form-select">
                    <option value="">All</option>
                    @foreach($auditableTypes as $type)
                        <option value="{{ $type }}" {{ request('auditable_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Event Type</label>
                <select name="event_type" class="form-select">
                    <option value="">All</option>
                    @foreach($eventTypes as $type)
                        <option value="{{ $type }}" {{ request('event_type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Risk Level</label>
                <select name="risk_level" class="form-select">
                    <option value="">All</option>
                    @foreach(['low', 'medium', 'high', 'critical'] as $risk)
                        <option value="{{ $risk }}" {{ request('risk_level') == $risk ? 'selected' : '' }}>{{ ucfirst($risk) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Approval Status</label>
                <select name="approval_status" class="form-select">
                    <option value="">All</option>
                    @foreach(['pending', 'approved', 'rejected'] as $status)
                        <option value="{{ $status }}" {{ request('approval_status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Description, user name...">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Apply</button>
                <a href="{{ route('class-data-audit.index') }}" class="btn btn-outline-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
 </div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="fas fa-list-ul me-2"></i>Audit Records</span>
        <span class="text-muted">Showing {{ $audits->count() }} of {{ $audits->total() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="auditsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Entity</th>
                        <th>Event</th>
                        <th>Risk</th>
                        <th>Approval</th>
                        <th>User</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($audits as $audit)
                        <tr>
                            <td>{{ $audit->id }}</td>
                            <td>{{ class_basename($audit->auditable_type) }} #{{ $audit->auditable_id }}</td>
                            <td>{{ ucfirst($audit->event_type) }}</td>
                            <td>
                                <span class="badge bg-{{ $audit->risk_level === 'critical' ? 'danger' : ($audit->risk_level === 'high' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($audit->risk_level) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $audit->approval_status === 'approved' ? 'success' : ($audit->approval_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($audit->approval_status ?? 'pending') }}
                                </span>
                            </td>
                            <td>{{ $audit->user_name ?? ($audit->user->name ?? 'Unknown') }}</td>
                            <td>{{ $audit->created_at ? $audit->created_at->format('Y-m-d H:i') : '' }}</td>
                            <td>
                                <a href="{{ route('class-data-audit.show', $audit) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No audit records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $audits->withQueryString()->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(function() {
        $('#auditsTable').DataTable({
            paging: false,
            info: false,
            searching: false,
            order: [[6, 'desc']]
        });
    });
</script>
@endpush
@endsection