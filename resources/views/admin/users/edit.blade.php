@extends('layouts.app')

@section('title', 'Admin - Edit User')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Edit User</h1>
            <p class="text-muted mb-0">Update account, status, and profile</p>
        </div>
        <div>
            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following errors.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="row g-3">
        @csrf
        @method('PUT')

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Account</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            @php($statuses = \App\Models\NewUser::getAvailableStatuses())
                            <select name="status" class="form-select" required>
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" @if(old('status', $user->status) === $key) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password (optional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="must_change_password" id="must_change_password" value="1" @if(old('must_change_password', $user->must_change_password)) checked @endif>
                                <label class="form-check-label" for="must_change_password">Must change password</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="two_factor_enabled" id="two_factor_enabled" value="1" @if(old('two_factor_enabled', $user->two_factor_enabled)) checked @endif>
                                <label class="form-check-label" for="two_factor_enabled">Enable 2FA</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Profile</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" value="{{ old('first_name', optional($user->profile)->first_name) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" value="{{ old('last_name', optional($user->profile)->last_name) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($user->profile)->date_of_birth) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                @php($g = old('gender', optional($user->profile)->gender))
                                <option value="">Select</option>
                                <option value="male" @if($g==='male') selected @endif>Male</option>
                                <option value="female" @if($g==='female') selected @endif>Female</option>
                                <option value="other" @if($g==='other') selected @endif>Other</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" value="{{ old('address', optional($user->profile)->address) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" value="{{ old('city', optional($user->profile)->city) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="state" value="{{ old('state', optional($user->profile)->state) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Postal Code</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', optional($user->profile)->postal_code) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" value="{{ old('country', optional($user->profile)->country) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', optional($user->profile)->emergency_contact_name) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Emergency Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', optional($user->profile)->emergency_contact_phone) }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Emergency Contact Relationship</label>
                            <input type="text" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', optional($user->profile)->emergency_contact_relationship) }}" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update</button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-secondary">Cancel</a>
                <a href="{{ route('admin.users.roles.show', $user) }}" class="btn btn-outline-info">Manage Roles</a>
            </div>
        </div>
    </form>
</div>
@endsection