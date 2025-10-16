@extends('layouts.app')

@section('title', 'Admin - User Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">User Details</h1>
            <p class="text-muted mb-0">Comprehensive user information and activity</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary"><i class="fas fa-edit me-1"></i> Edit</a>
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete this user?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Delete</button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Account</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted">Username</label>
                            <div>{{ $user->username }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted">Email</label>
                            <div>{{ $user->email }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted">Phone</label>
                            <div>{{ $user->phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted">Status</label>
                            <div><span class="badge bg-secondary">{{ $user->status }}</span></div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted">School</label>
                            <div>{{ optional($user->school)->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted">Roles</label>
                            <div>
                                @if ($user->roles && count($user->roles))
                                    @foreach ($user->roles as $assignment)
                                        <span class="badge bg-info text-dark me-1">{{ optional($assignment->role)->name }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                                <a href="{{ route('admin.users.roles.show', $user) }}" class="btn btn-sm btn-outline-info ms-2">Manage Roles</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Profile</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted">Name</label>
                            <div>{{ optional($user->profile)->first_name }} {{ optional($user->profile)->last_name }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted">DOB</label>
                            <div>{{ optional($user->profile)->date_of_birth ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted">Gender</label>
                            <div>{{ optional($user->profile)->gender ?? '—' }}</div>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted">Address</label>
                            <div>{{ optional($user->profile)->address ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted">City</label>
                            <div>{{ optional($user->profile)->city ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted">State</label>
                            <div>{{ optional($user->profile)->state ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted">Postal Code</label>
                            <div>{{ optional($user->profile)->postal_code ?? '—' }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="text-muted">Country</label>
                            <div>{{ optional($user->profile)->country ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted">Emergency Contact</label>
                            <div>{{ optional($user->profile)->emergency_contact_name ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted">Emergency Phone</label>
                            <div>{{ optional($user->profile)->emergency_contact_phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted">Relationship</label>
                            <div>{{ optional($user->profile)->emergency_contact_relationship ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">Statistics</div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between"><span class="text-muted">Total Sessions</span><strong>{{ $statistics['total_sessions'] ?? 0 }}</strong></li>
                        <li class="d-flex justify-content-between"><span class="text-muted">Active Sessions</span><strong>{{ $statistics['active_sessions'] ?? 0 }}</strong></li>
                        <li class="d-flex justify-content-between"><span class="text-muted">Total Activities</span><strong>{{ $statistics['total_activities'] ?? 0 }}</strong></li>
                        <li class="d-flex justify-content-between"><span class="text-muted">Last Login</span><strong>{{ $statistics['last_login'] ? \Carbon\Carbon::parse($statistics['last_login'])->format('Y-m-d H:i') : '—' }}</strong></li>
                        <li class="d-flex justify-content-between"><span class="text-muted">Failed Login Attempts</span><strong>{{ $statistics['failed_login_attempts'] ?? 0 }}</strong></li>
                        <li class="d-flex justify-content-between"><span class="text-muted">Roles</span><strong>{{ $statistics['roles_count'] ?? 0 }}</strong></li>
                        <li class="d-flex justify-content-between"><span class="text-muted">Active Roles</span><strong>{{ $statistics['active_roles_count'] ?? 0 }}</strong></li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Quick Actions</div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary">Edit Account</a>
                    <a href="{{ route('admin.users.roles.show', $user) }}" class="btn btn-outline-info">Manage Roles</a>
                    <button type="button" class="btn btn-outline-warning" onclick="lockUser('{{ route('admin.users.lock-account', $user) }}')" disabled>Lock Account</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection