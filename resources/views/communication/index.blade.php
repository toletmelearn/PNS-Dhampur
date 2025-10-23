@extends('layouts.app')

@section('title', 'Communication Center')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Communication Center</h4>
        @if(session('status'))
            <span class="badge bg-success">{{ session('status') }}</span>
        @endif
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('communication.send') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-12 col-md-3">
                    <label class="form-label">Channel</label>
                    <select name="channel" class="form-select" required>
                        <option value="sms">SMS</option>
                        <option value="email">Email</option>
                        <option value="both">Both</option>
                    </select>
                </div>
                <div class="col-12 col-md-9">
                    <label class="form-label">Recipients (comma or newline separated phone/email)</label>
                    <textarea name="recipients" class="form-control" rows="2" required>{{ old('recipients') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Subject (for email)</label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" placeholder="Optional">
                </div>
                <div class="col-12">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="5" required>{{ old('message') }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
