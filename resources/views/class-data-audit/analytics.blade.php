@extends('layouts.app')

@section('title', 'Audit Analytics')

@section('content')
@php
    $breadcrumbs = [
        ['title' => 'Dashboard', 'url' => route('dashboard')],
        ['title' => 'Class Data Audit', 'url' => route('class-data-audit.index')],
        ['title' => 'Analytics', 'url' => '']
    ];
    $stats = $statistics ?? [];
    $dailyCounts = $analytics['daily_counts'] ?? [];
    $riskCounts = $analytics['risk_counts'] ?? [];
    $approvalCounts = $analytics['approval_counts'] ?? [];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="fas fa-chart-line me-2"></i>Audit Analytics</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('class-data-audit.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-clipboard-list me-1"></i> Back to Audit List
        </a>
        @if(auth()->user()->hasPermission('export_audit_reports'))
            <form action="{{ route('class-data-audit.export') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="format" value="excel">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <small>Total Audits</small>
            <h3>{{ $stats['total_audits'] ?? 0 }}</h3>
            <div><i class="fas fa-clipboard-list"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);">
            <small>Pending Approvals</small>
            <h3>{{ $stats['pending_approvals'] ?? 0 }}</h3>
            <div><i class="fas fa-hourglass-half"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
            <small>High Risk</small>
            <h3>{{ $stats['high_risk_changes'] ?? 0 }}</h3>
            <div><i class="fas fa-triangle-exclamation"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%);">
            <small>Critical</small>
            <h3>{{ $stats['critical_changes'] ?? 0 }}</h3>
            <div><i class="fas fa-skull-crossbones"></i></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-chart-area me-2"></i>Audits Over Time
            </div>
            <div class="card-body">
                <canvas id="auditsOverTimeChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-chart-pie me-2"></i>Risk Distribution
            </div>
            <div class="card-body">
                <canvas id="riskDistributionChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-chart-bar me-2"></i>Approval Status Trends
            </div>
            <div class="card-body">
                <canvas id="approvalStatusChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white fw-bold">
                <i class="fas fa-user-clock me-2"></i>User Activity (Top 10)
            </div>
            <div class="card-body">
                <canvas id="userActivityChart" height="160"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        const dailyCounts = @json($dailyCounts);
        const riskCounts = @json($riskCounts);
        const approvalCounts = @json($approvalCounts);

        // Audits Over Time
        const timeLabels = Object.keys(dailyCounts || {});
        const timeValues = Object.values(dailyCounts || {});
        new Chart(document.getElementById('auditsOverTimeChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'Audits',
                    data: timeValues,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });

        // Risk Distribution
        const riskLabels = Object.keys(riskCounts || {});
        const riskValues = Object.values(riskCounts || {});
        new Chart(document.getElementById('riskDistributionChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: riskLabels.map(r => r.charAt(0).toUpperCase() + r.slice(1)),
                datasets: [{
                    data: riskValues,
                    backgroundColor: ['#6b7280', '#f59e0b', '#ef4444', '#7f1d1d']
                }]
            }
        });

        // Approval Status Trends
        const approvalLabels = Object.keys(approvalCounts || {});
        const approvalValues = Object.values(approvalCounts || {});
        new Chart(document.getElementById('approvalStatusChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: approvalLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
                datasets: [{
                    label: 'Count',
                    data: approvalValues,
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });

        // User Activity - optional: expects analytics.user_activity { name: count }
        const userActivity = @json(($analytics['user_activity'] ?? []));
        const userLabels = Object.keys(userActivity || {});
        const userValues = Object.values(userActivity || {});
        new Chart(document.getElementById('userActivityChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: userLabels,
                datasets: [{
                    label: 'Actions',
                    data: userValues,
                    backgroundColor: '#1e40af'
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    })();
</script>
@endpush
@endsection