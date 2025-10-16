@extends('layouts.app')

@section('title', 'Admin - Users')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">User Management</h1>
            <p class="text-muted mb-0">List, search, filter, and manage users</p>
        </div>
        <div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> Create User
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Statistics -->
    @isset($statistics)
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total Users</span>
                        <strong>{{ $statistics['total_users'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Active</span>
                        <strong>{{ $statistics['active_users'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Locked</span>
                        <strong>{{ $statistics['locked_users'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Suspended</span>
                        <strong>{{ $statistics['suspended_users'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endisset

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-header">Filters & Search</div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, email, username, phone" />
                </div>
                <div class="col-md-3">
                    <label for="role" class="form-label">Role</label>
                    <select id="role" name="role" class="form-select">
                        <option value="">All</option>
                        @isset($roles)
                            @foreach ($roles as $role)
                                <option value="{{ $role->slug }}" @if(request('role') === $role->slug) selected @endif>{{ $role->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="school_id" class="form-label">School</label>
                    <select id="school_id" name="school_id" class="form-select">
                        <option value="">All</option>
                        @isset($schools)
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @if((string)request('school_id') === (string)$school->id) selected @endif>{{ $school->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        @isset($statuses)
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @if(request('status') === $key) selected @endif>{{ $label }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="verification" class="form-label">Verification</label>
                    <select id="verification" name="verification" class="form-select">
                        <option value="">All</option>
                        <option value="email_verified" @if(request('verification')==='email_verified') selected @endif>Email Verified</option>
                        <option value="email_unverified" @if(request('verification')==='email_unverified') selected @endif>Email Unverified</option>
                        <option value="phone_verified" @if(request('verification')==='phone_verified') selected @endif>Phone Verified</option>
                        <option value="phone_unverified" @if(request('verification')==='phone_unverified') selected @endif>Phone Unverified</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sort By</label>
                    <div class="input-group">
                        <select name="sort_by" class="form-select">
                            <option value="created_at" @if(request('sort_by')==='created_at') selected @endif>Created</option>
                            <option value="name" @if(request('sort_by')==='name') selected @endif>Name</option>
                            <option value="email" @if(request('sort_by')==='email') selected @endif>Email</option>
                            <option value="status" @if(request('sort_by')==='status') selected @endif>Status</option>
                        </select>
                        <select name="sort_order" class="form-select" style="max-width: 130px">
                            <option value="asc" @if(request('sort_order')==='asc') selected @endif>Asc</option>
                            <option value="desc" @if(request('sort_order')==='desc') selected @endif>Desc</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-12 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="fas fa-filter me-1"></i> Apply</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>School</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $u)
                            <tr>
                                <td>{{ $u->username }}</td>
                                <td>{{ optional($u->profile)->first_name }} {{ optional($u->profile)->last_name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>
                                    @if ($u->roles && count($u->roles))
                                        @foreach ($u->roles as $assignment)
                                            <span class="badge bg-info text-dark me-1">{{ optional($assignment->role)->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td>{{ optional($u->school)->name ?? '—' }}</td>
                                <td><span class="badge bg-secondary">{{ $u->status }}</span></td>
                                <td>{{ $u->created_at ? $u->created_at->format('Y-m-d') : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.show', $u) }}" class="btn btn-sm btn-outline-info">View</a>
                                    <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection