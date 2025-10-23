@extends('layouts.app')

@section('title', 'Fee Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Fee Management</h3>
                </div>
                <div class="card-body">
                    @if(isset($stats))
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <div class="border rounded p-2">
                                <div class="text-muted">Total Fees</div>
                                <div class="h5">{{ $stats['total_fees'] }}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-2">
                                <div class="text-muted">Total Amount</div>
                                <div class="h5">₹ {{ number_format($stats['total_amount'], 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-2">
                                <div class="text-muted">Collected</div>
                                <div class="h5">₹ {{ number_format($stats['total_collected'], 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-2">
                                <div class="text-muted">Pending</div>
                                <div class="h5">₹ {{ number_format($stats['pending_amount'], 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="border rounded p-2">
                                <div class="text-muted">Overdue</div>
                                <div class="h5">{{ $stats['overdue_count'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="btn-group mb-3" role="group">
                        <a class="btn btn-primary" href="{{ route('fee-structures.index') }}">Manage Fee Structures</a>
                        <a class="btn btn-outline-primary" href="{{ route('student-fees.index') }}">Student Fees</a>
                        <a class="btn btn-outline-secondary" href="{{ route('payment.settings') }}">Payment Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection