<!-- Export Options Modal -->
<div class="modal fade" id="exportOptionsModal" tabindex="-1" aria-labelledby="exportOptionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportOptionsModalLabel">Export Audit Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="mb-3">
                        <label for="exportFormat" class="form-label">Export Format</label>
                        <select class="form-select" id="exportFormat" name="format" required>
                            <option value="">Select format...</option>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="dateRange" class="form-label">Date Range</label>
                        <select class="form-select" id="dateRange" name="date_range">
                            <option value="all">All Records</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div id="customDateRange" class="mb-3" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate" name="start_date">
                            </div>
                            <div class="col-md-6">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="endDate" name="end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="statusFilter" class="form-label">Status Filter</label>
                        <select class="form-select" id="statusFilter" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeVersions" name="include_versions" value="1">
                            <label class="form-check-label" for="includeVersions">
                                Include Version History
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="exportBtn">Export</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateRangeSelect = document.getElementById('dateRange');
    const customDateRange = document.getElementById('customDateRange');
    const exportBtn = document.getElementById('exportBtn');
    const exportForm = document.getElementById('exportForm');
    
    // Show/hide custom date range
    dateRangeSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateRange.style.display = 'block';
        } else {
            customDateRange.style.display = 'none';
        }
    });
    
    // Handle export
    exportBtn.addEventListener('click', function() {
        const formData = new FormData(exportForm);
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }
        
        // Validate required fields
        const format = formData.get('format');
        if (!format) {
            alert('Please select an export format.');
            return;
        }
        
        // Validate custom date range if selected
        if (dateRangeSelect.value === 'custom') {
            const startDate = formData.get('start_date');
            const endDate = formData.get('end_date');
            
            if (!startDate || !endDate) {
                alert('Please select both start and end dates for custom range.');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be later than end date.');
                return;
            }
        }
        
        // Create download URL
        const exportUrl = `/class-data-audit/export?${params.toString()}`;
        
        // Trigger download
        window.open(exportUrl, '_blank');
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportOptionsModal'));
        modal.hide();
        
        // Reset form
        exportForm.reset();
        customDateRange.style.display = 'none';
    });
});
</script>