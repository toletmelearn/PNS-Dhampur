<!-- Version Details Modal -->
<div class="modal fade" id="versionDetailsModal" tabindex="-1" aria-labelledby="versionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="versionDetailsModalLabel">
                    <i class="fas fa-code-branch"></i> Version Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="versionDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading version details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="compareVersionBtn" style="display: none;">
                    <i class="fas fa-exchange-alt"></i> Compare Versions
                </button>
                <button type="button" class="btn btn-warning" id="rollbackVersionBtn" style="display: none;">
                    <i class="fas fa-undo"></i> Rollback to This Version
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Version Comparison Modal -->
<div class="modal fade" id="versionComparisonModal" tabindex="-1" aria-labelledby="versionComparisonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="versionComparisonModalLabel">
                    <i class="fas fa-exchange-alt"></i> Version Comparison
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="compareFromVersion" class="form-label">From Version</label>
                        <select class="form-select" id="compareFromVersion">
                            <option value="">Select version...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="compareToVersion" class="form-label">To Version</label>
                        <select class="form-select" id="compareToVersion">
                            <option value="">Select version...</option>
                        </select>
                    </div>
                </div>
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-primary" onclick="performVersionComparison()">
                        <i class="fas fa-search"></i> Compare
                    </button>
                </div>
                <div id="versionComparisonContent">
                    <!-- Comparison results will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function renderVersionDetails(version) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Version Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td><strong>Version Number:</strong></td>
                                <td><span class="badge bg-info">v${version.version_number}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Version Type:</strong></td>
                                <td><span class="badge bg-secondary">${version.version_type}</span></td>
                            </tr>
                            <tr>
                                <td><strong>Current Version:</strong></td>
                                <td>
                                    ${version.is_current_version ? 
                                        '<span class="badge bg-success">Yes</span>' : 
                                        '<span class="badge bg-secondary">No</span>'
                                    }
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Created By:</strong></td>
                                <td>${version.created_by_name || 'System'}</td>
                            </tr>
                            <tr>
                                <td><strong>Created At:</strong></td>
                                <td>${new Date(version.created_at).toLocaleString()}</td>
                            </tr>
                            <tr>
                                <td><strong>Data Size:</strong></td>
                                <td>${formatBytes(version.data_size)}</td>
                            </tr>
                            <tr>
                                <td><strong>Compression:</strong></td>
                                <td>${version.compression_type || 'None'}</td>
                            </tr>
                            <tr>
                                <td><strong>Checksum:</strong></td>
                                <td>
                                    <code>${version.checksum ? version.checksum.substring(0, 16) + '...' : 'N/A'}</code>
                                    ${version.integrity_verified ? 
                                        '<i class="fas fa-check-circle text-success ms-1" title="Integrity verified"></i>' : 
                                        '<i class="fas fa-exclamation-triangle text-danger ms-1" title="Integrity check failed"></i>'
                                    }
                                </td>
                            </tr>
                        </table>
                        
                        ${version.changes_summary ? `
                            <div class="mt-3">
                                <strong>Changes Summary:</strong>
                                <p class="mt-2">${version.changes_summary}</p>
                            </div>
                        ` : ''}
                        
                        ${version.tags && version.tags.length > 0 ? `
                            <div class="mt-3">
                                <strong>Tags:</strong>
                                <div class="mt-2">
                                    ${version.tags.map(tag => `<span class="badge bg-secondary me-1">${tag}</span>`).join('')}
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-database"></i> Data Snapshot</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="dataViewType" id="dataViewRaw" value="raw" checked>
                                <label class="btn btn-outline-primary btn-sm" for="dataViewRaw">Raw Data</label>
                                
                                <input type="radio" class="btn-check" name="dataViewType" id="dataViewFormatted" value="formatted">
                                <label class="btn btn-outline-primary btn-sm" for="dataViewFormatted">Formatted</label>
                                
                                <input type="radio" class="btn-check" name="dataViewType" id="dataViewTable" value="table">
                                <label class="btn btn-outline-primary btn-sm" for="dataViewTable">Table View</label>
                            </div>
                        </div>
                        
                        <div id="dataSnapshotContent">
                            <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code id="dataSnapshotCode">${JSON.stringify(version.data_snapshot, null, 2)}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        ${version.metadata && Object.keys(version.metadata).length > 0 ? `
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-tags"></i> Metadata</h6>
                        </div>
                        <div class="card-body">
                            <pre class="bg-light p-3 rounded"><code>${JSON.stringify(version.metadata, null, 2)}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        ` : ''}
    `;
    
    $('#versionDetailsContent').html(content);
    
    // Show action buttons if applicable
    if (version.can_rollback) {
        $('#rollbackVersionBtn').show().data('version-id', version.id);
    }
    $('#compareVersionBtn').show().data('version-id', version.id);
    
    // Setup data view type handlers
    $('input[name="dataViewType"]').on('change', function() {
        updateDataView(version.data_snapshot, $(this).val());
    });
}

function updateDataView(dataSnapshot, viewType) {
    const container = $('#dataSnapshotContent');
    
    switch (viewType) {
        case 'raw':
            container.html(`<pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>${JSON.stringify(dataSnapshot, null, 2)}</code></pre>`);
            break;
            
        case 'formatted':
            container.html(formatDataAsHtml(dataSnapshot));
            break;
            
        case 'table':
            container.html(formatDataAsTable(dataSnapshot));
            break;
    }
}

function formatDataAsHtml(data) {
    if (typeof data !== 'object' || data === null) {
        return `<div class="alert alert-info">Data is not in object format</div>`;
    }
    
    let html = '<div class="row">';
    for (const [key, value] of Object.entries(data)) {
        html += `
            <div class="col-md-6 mb-3">
                <div class="border p-3 rounded">
                    <strong>${key}:</strong>
                    <div class="mt-2">
                        ${typeof value === 'object' ? 
                            `<pre class="bg-light p-2 rounded small"><code>${JSON.stringify(value, null, 2)}</code></pre>` : 
                            `<span class="text-muted">${value}</span>`
                        }
                    </div>
                </div>
            </div>
        `;
    }
    html += '</div>';
    return html;
}

function formatDataAsTable(data) {
    if (typeof data !== 'object' || data === null) {
        return `<div class="alert alert-info">Data is not in object format</div>`;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    for (const [key, value] of Object.entries(data)) {
        const valueType = typeof value;
        const displayValue = valueType === 'object' ? 
            JSON.stringify(value) : 
            String(value);
            
        html += `
            <tr>
                <td><strong>${key}</strong></td>
                <td class="text-break" style="max-width: 300px;">${displayValue}</td>
                <td><span class="badge bg-secondary">${valueType}</span></td>
            </tr>
        `;
    }
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    return html;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function performVersionComparison() {
    const fromVersion = $('#compareFromVersion').val();
    const toVersion = $('#compareToVersion').val();
    
    if (!fromVersion || !toVersion) {
        showAlert('Please select both versions to compare', 'warning');
        return;
    }
    
    if (fromVersion === toVersion) {
        showAlert('Please select different versions to compare', 'warning');
        return;
    }
    
    $.get(`/class-data-audit/versions/compare?from=${fromVersion}&to=${toVersion}`)
        .done(function(response) {
            if (response.success) {
                renderVersionComparison(response.data);
            } else {
                showAlert(response.message || 'Comparison failed', 'error');
            }
        })
        .fail(function() {
            showAlert('Failed to compare versions', 'error');
        });
}

function renderVersionComparison(comparison) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-minus-circle text-danger"></i> Version ${comparison.from_version.version_number}</h6>
                <div class="bg-light p-3 rounded">
                    <small class="text-muted">Created: ${new Date(comparison.from_version.created_at).toLocaleString()}</small>
                    <pre class="mt-2"><code>${JSON.stringify(comparison.from_version.data_snapshot, null, 2)}</code></pre>
                </div>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-plus-circle text-success"></i> Version ${comparison.to_version.version_number}</h6>
                <div class="bg-light p-3 rounded">
                    <small class="text-muted">Created: ${new Date(comparison.to_version.created_at).toLocaleString()}</small>
                    <pre class="mt-2"><code>${JSON.stringify(comparison.to_version.data_snapshot, null, 2)}</code></pre>
                </div>
            </div>
        </div>
        
        ${comparison.differences && comparison.differences.length > 0 ? `
            <div class="mt-4">
                <h6><i class="fas fa-exchange-alt"></i> Differences</h6>
                <div class="list-group">
                    ${comparison.differences.map(diff => `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${diff.field}</h6>
                                <small class="text-muted">${diff.type}</small>
                            </div>
                            <p class="mb-1">
                                <span class="text-danger">- ${diff.old_value}</span><br>
                                <span class="text-success">+ ${diff.new_value}</span>
                            </p>
                        </div>
                    `).join('')}
                </div>
            </div>
        ` : '<div class="alert alert-info mt-4">No differences found between these versions.</div>'}
    `;
    
    $('#versionComparisonContent').html(content);
}
</script>