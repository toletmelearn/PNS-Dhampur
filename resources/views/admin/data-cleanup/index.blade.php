@extends('layouts.admin')

@section('title', 'Data Cleanup Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-broom mr-2"></i>
                        Data Cleanup Dashboard
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.data-cleanup.download-report') }}" class="btn btn-sm btn-info">
                            <i class="fas fa-download mr-1"></i>
                            Download Report
                        </a>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="refreshReport()">
                            <i class="fas fa-sync-alt mr-1"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $report['orphaned_students'] ?? 0 }}</h3>
                                    <p>Orphaned Students</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-times"></i>
                                </div>
                                <a href="{{ route('admin.data-cleanup.orphaned') }}" class="small-box-footer">
                                    View Details <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $report['duplicate_students'] ?? 0 }}</h3>
                                    <p>Duplicate Students</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-copy"></i>
                                </div>
                                <a href="{{ route('admin.data-cleanup.duplicates') }}" class="small-box-footer">
                                    View Details <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $report['consistency_issues'] ?? 0 }}</h3>
                                    <p>Consistency Issues</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <a href="{{ route('admin.data-cleanup.consistency') }}" class="small-box-footer">
                                    View Details <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $report['archivable_records'] ?? 0 }}</h3>
                                    <p>Archivable Records</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-archive"></i>
                                </div>
                                <a href="{{ route('admin.data-cleanup.archive') }}" class="small-box-footer">
                                    Archive Data <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Report -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Data Quality Issues</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td>Students without classes</td>
                                                    <td>
                                                        <span class="badge badge-warning">
                                                            {{ $report['orphaned_students'] ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Duplicate Aadhaar numbers</td>
                                                    <td>
                                                        <span class="badge badge-danger">
                                                            {{ $report['duplicate_aadhaar'] ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Duplicate admission numbers</td>
                                                    <td>
                                                        <span class="badge badge-danger">
                                                            {{ $report['duplicate_admission_no'] ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Invalid user references</td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            {{ $report['invalid_user_refs'] ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Classes without teachers</td>
                                                    <td>
                                                        <span class="badge badge-info">
                                                            {{ $report['classes_without_teachers'] ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">System Statistics</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td>Total Students</td>
                                                    <td>
                                                        <span class="badge badge-primary">
                                                            {{ $report['total_students'] ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Active Students</td>
                                                    <td>
                                                        <span class="badge badge-success">
                                                            {{ $report['active_students'] ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Total Classes</td>
                                                    <td>
                                                        <span class="badge badge-primary">
                                                            {{ $report['total_classes'] ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Active Classes</td>
                                                    <td>
                                                        <span class="badge badge-success">
                                                            {{ $report['active_classes'] ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Last Cleanup</td>
                                                    <td>
                                                        <small class="text-muted">
                                                            {{ $report['last_cleanup'] ?? 'Never' }}
                                                        </small>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Quick Actions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="btn-group-vertical btn-group-lg d-block d-md-none">
                                        <a href="{{ route('admin.data-cleanup.orphaned') }}" class="btn btn-warning mb-2">
                                            <i class="fas fa-user-times mr-2"></i>
                                            Fix Orphaned Records
                                        </a>
                                        <a href="{{ route('admin.data-cleanup.duplicates') }}" class="btn btn-danger mb-2">
                                            <i class="fas fa-copy mr-2"></i>
                                            Merge Duplicates
                                        </a>
                                        <a href="{{ route('admin.data-cleanup.consistency') }}" class="btn btn-info mb-2">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            Check Consistency
                                        </a>
                                        <a href="{{ route('admin.data-cleanup.archive') }}" class="btn btn-success">
                                            <i class="fas fa-archive mr-2"></i>
                                            Archive Old Data
                                        </a>
                                    </div>
                                    
                                    <div class="btn-group d-none d-md-flex">
                                        <a href="{{ route('admin.data-cleanup.orphaned') }}" class="btn btn-warning">
                                            <i class="fas fa-user-times mr-2"></i>
                                            Fix Orphaned Records
                                        </a>
                                        <a href="{{ route('admin.data-cleanup.duplicates') }}" class="btn btn-danger">
                                            <i class="fas fa-copy mr-2"></i>
                                            Merge Duplicates
                                        </a>
                                        <a href="{{ route('admin.data-cleanup.consistency') }}" class="btn btn-info">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            Check Consistency
                                        </a>
                                        <a href="{{ route('admin.data-cleanup.archive') }}" class="btn btn-success">
                                            <i class="fas fa-archive mr-2"></i>
                                            Archive Old Data
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function refreshReport() {
    // Show loading state
    const refreshBtn = document.querySelector('button[onclick="refreshReport()"]');
    const originalHtml = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Refreshing...';
    refreshBtn.disabled = true;
    
    // Reload the page to get fresh data
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Auto-refresh every 5 minutes
setInterval(() => {
    fetch('{{ route("admin.data-cleanup.api.report") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the summary cards with new data
                updateSummaryCards(data.data);
            }
        })
        .catch(error => {
            console.error('Error refreshing report:', error);
        });
}, 300000); // 5 minutes

function updateSummaryCards(report) {
    // Update orphaned students count
    const orphanedElement = document.querySelector('.small-box.bg-warning h3');
    if (orphanedElement) {
        orphanedElement.textContent = report.orphaned_students || 0;
    }
    
    // Update duplicate students count
    const duplicateElement = document.querySelector('.small-box.bg-danger h3');
    if (duplicateElement) {
        duplicateElement.textContent = report.duplicate_students || 0;
    }
    
    // Update consistency issues count
    const consistencyElement = document.querySelector('.small-box.bg-info h3');
    if (consistencyElement) {
        consistencyElement.textContent = report.consistency_issues || 0;
    }
    
    // Update archivable records count
    const archivableElement = document.querySelector('.small-box.bg-success h3');
    if (archivableElement) {
        archivableElement.textContent = report.archivable_records || 0;
    }
}
</script>
@endpush