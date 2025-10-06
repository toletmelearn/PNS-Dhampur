/**
 * Enhanced File Upload JavaScript
 * Provides progress indicators, drag-and-drop, and file preview functionality
 */

class EnhancedFileUpload {
    constructor(options = {}) {
        this.options = {
            maxFileSize: options.maxFileSize || 10 * 1024 * 1024, // 10MB default
            allowedTypes: options.allowedTypes || ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
            progressContainer: options.progressContainer || '.upload-progress',
            previewContainer: options.previewContainer || '.file-preview',
            dropZone: options.dropZone || '.drop-zone',
            fileInput: options.fileInput || 'input[type="file"]',
            ...options
        };
        
        this.files = [];
        this.init();
    }

    init() {
        this.setupDropZone();
        this.setupFileInput();
        this.setupProgressIndicator();
    }

    setupDropZone() {
        const dropZones = document.querySelectorAll(this.options.dropZone);
        
        dropZones.forEach(dropZone => {
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, this.preventDefaults, false);
                document.body.addEventListener(eventName, this.preventDefaults, false);
            });

            // Highlight drop zone when item is dragged over it
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => this.highlight(dropZone), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => this.unhighlight(dropZone), false);
            });

            // Handle dropped files
            dropZone.addEventListener('drop', (e) => this.handleDrop(e, dropZone), false);
            
            // Handle click to open file dialog
            dropZone.addEventListener('click', () => {
                const fileInput = dropZone.querySelector('input[type="file"]') || 
                                document.querySelector(this.options.fileInput);
                if (fileInput) fileInput.click();
            });
        });
    }

    setupFileInput() {
        const fileInputs = document.querySelectorAll(this.options.fileInput);
        
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleFiles(e.target.files, input));
        });
    }

    setupProgressIndicator() {
        // Create progress container if it doesn't exist
        const containers = document.querySelectorAll(this.options.progressContainer);
        if (containers.length === 0) {
            const container = document.createElement('div');
            container.className = 'upload-progress';
            container.style.display = 'none';
            document.body.appendChild(container);
        }
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    highlight(element) {
        element.classList.add('drag-over');
    }

    unhighlight(element) {
        element.classList.remove('drag-over');
    }

    handleDrop(e, dropZone) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        const fileInput = dropZone.querySelector('input[type="file"]') || 
                         document.querySelector(this.options.fileInput);
        
        this.handleFiles(files, fileInput);
    }

    handleFiles(files, input) {
        [...files].forEach(file => this.processFile(file, input));
    }

    processFile(file, input) {
        // Validate file
        if (!this.validateFile(file)) {
            return;
        }

        // Add to files array
        this.files.push(file);

        // Show preview
        this.showPreview(file, input);

        // If this is part of a form, we'll handle upload on form submission
        // Otherwise, you can trigger upload immediately
        if (this.options.autoUpload) {
            this.uploadFile(file, input);
        }
    }

    validateFile(file) {
        // Check file size
        if (file.size > this.options.maxFileSize) {
            this.showError(`File "${file.name}" is too large. Maximum size is ${this.formatFileSize(this.options.maxFileSize)}.`);
            return false;
        }

        // Check file type
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!this.options.allowedTypes.includes(fileExtension)) {
            this.showError(`File type "${fileExtension}" is not allowed. Allowed types: ${this.options.allowedTypes.join(', ')}.`);
            return false;
        }

        return true;
    }

    showPreview(file, input) {
        const previewContainer = input.closest('form')?.querySelector(this.options.previewContainer) ||
                               document.querySelector(this.options.previewContainer);
        
        if (!previewContainer) return;

        const previewElement = document.createElement('div');
        previewElement.className = 'file-preview-item';
        previewElement.innerHTML = this.generatePreviewHTML(file);

        previewContainer.appendChild(previewElement);

        // Add remove functionality
        const removeBtn = previewElement.querySelector('.remove-file');
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                this.removeFile(file, previewElement);
            });
        }
    }

    generatePreviewHTML(file) {
        const fileSize = this.formatFileSize(file.size);
        const fileType = file.type || 'application/octet-stream';
        const fileName = file.name;
        const fileExtension = fileName.split('.').pop().toLowerCase();

        let previewContent = '';
        let previewActions = '';
        
        // Generate preview based on file type
        if (fileType.startsWith('image/')) {
            const imageUrl = URL.createObjectURL(file);
            previewContent = `
                <div class="file-preview-image">
                    <img src="${imageUrl}" alt="${fileName}" style="max-width: 100px; max-height: 100px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="this.showImageModal('${imageUrl}', '${fileName}')">
                </div>
            `;
            previewActions = `
                <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="this.showImageModal('${imageUrl}', '${fileName}')">
                    <i class="fas fa-eye"></i> Preview
                </button>
            `;
        } else if (fileType === 'application/pdf') {
            const pdfUrl = URL.createObjectURL(file);
            previewContent = `
                <div class="file-preview-pdf">
                    <i class="fas fa-file-pdf" style="font-size: 48px; color: #dc3545;"></i>
                </div>
            `;
            previewActions = `
                <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="this.showPdfModal('${pdfUrl}', '${fileName}')">
                    <i class="fas fa-eye"></i> Preview PDF
                </button>
            `;
        } else {
            previewContent = `
                <div class="file-preview-document">
                    <i class="fas fa-file-alt" style="font-size: 48px; color: #6c757d;"></i>
                </div>
            `;
        }

        return `
            <div class="d-flex align-items-center p-3 border rounded mb-2">
                ${previewContent}
                <div class="flex-grow-1 ms-3">
                    <div class="fw-bold">${fileName}</div>
                    <div class="text-muted small">${fileSize} • ${fileExtension.toUpperCase()}</div>
                    <div class="progress mt-2" style="height: 4px; display: none;">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="mt-2">
                        ${previewActions}
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-file">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }

    removeFile(file, previewElement) {
        // Remove from files array
        const index = this.files.indexOf(file);
        if (index > -1) {
            this.files.splice(index, 1);
        }

        // Remove preview element
        previewElement.remove();

        // Revoke object URL if it's an image
        const img = previewElement.querySelector('img');
        if (img) {
            URL.revokeObjectURL(img.src);
        }
    }

    uploadFile(file, input) {
        const formData = new FormData();
        formData.append('file', file);

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('_token', csrfToken);
        }

        // Find the preview element for this file
        const previewElement = this.findPreviewElement(file);
        const progressBar = previewElement?.querySelector('.progress-bar');
        const progressContainer = previewElement?.querySelector('.progress');

        if (progressContainer) {
            progressContainer.style.display = 'block';
        }

        // Create XMLHttpRequest for progress tracking
        const xhr = new XMLHttpRequest();

        // Track upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable && progressBar) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                progressBar.setAttribute('aria-valuenow', percentComplete);
            }
        });

        // Handle completion
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                this.showSuccess(`File "${file.name}" uploaded successfully.`);
                if (progressBar) {
                    progressBar.classList.add('bg-success');
                }
            } else {
                this.showError(`Failed to upload "${file.name}".`);
                if (progressBar) {
                    progressBar.classList.add('bg-danger');
                }
            }
        });

        // Handle errors
        xhr.addEventListener('error', () => {
            this.showError(`Error uploading "${file.name}".`);
            if (progressBar) {
                progressBar.classList.add('bg-danger');
            }
        });

        // Send the request
        const uploadUrl = input.dataset.uploadUrl || '/upload';
        xhr.open('POST', uploadUrl);
        xhr.send(formData);
    }

    findPreviewElement(file) {
        const previewItems = document.querySelectorAll('.file-preview-item');
        for (let item of previewItems) {
            const fileName = item.querySelector('.fw-bold')?.textContent;
            if (fileName === file.name) {
                return item;
            }
        }
        return null;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showError(message) {
        this.showNotification(message, 'danger');
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Method to handle form submission with progress
    handleFormSubmission(form) {
        const formData = new FormData(form);
        const xhr = new XMLHttpRequest();

        // Show overall progress
        this.showOverallProgress();

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                this.updateOverallProgress(percentComplete);
            }
        });

        xhr.addEventListener('load', () => {
            this.hideOverallProgress();
            if (xhr.status === 200) {
                this.showSuccess('Form submitted successfully!');
                // Handle successful response
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                } catch (e) {
                    // If not JSON, might be a redirect or HTML response
                    window.location.reload();
                }
            } else {
                this.showError('Form submission failed. Please try again.');
            }
        });

        xhr.addEventListener('error', () => {
            this.hideOverallProgress();
            this.showError('Network error. Please check your connection and try again.');
        });

        xhr.open('POST', form.action);
        xhr.send(formData);

        return false; // Prevent default form submission
    }

    showOverallProgress() {
        let progressModal = document.getElementById('upload-progress-modal');
        if (!progressModal) {
            progressModal = document.createElement('div');
            progressModal.id = 'upload-progress-modal';
            progressModal.className = 'modal fade';
            progressModal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center p-4">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h5>Uploading files...</h5>
                            <div class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="mt-2 text-muted">
                                <span id="upload-percentage">0%</span> complete
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(progressModal);
        }

        // Show modal using Bootstrap
        const modal = new bootstrap.Modal(progressModal);
        modal.show();
    }

    updateOverallProgress(percentage) {
        const progressBar = document.querySelector('#upload-progress-modal .progress-bar');
        const percentageText = document.querySelector('#upload-percentage');
        
        if (progressBar) {
            progressBar.style.width = percentage + '%';
        }
        if (percentageText) {
            percentageText.textContent = Math.round(percentage) + '%';
        }
    }

    hideOverallProgress() {
        const progressModal = document.getElementById('upload-progress-modal');
        if (progressModal) {
            const modal = bootstrap.Modal.getInstance(progressModal);
            if (modal) {
                modal.hide();
            }
        }
    }

    // Enhanced preview methods for images and PDFs
    showImageModal(imageUrl, fileName) {
        let imageModal = document.getElementById('image-preview-modal');
        if (!imageModal) {
            imageModal = document.createElement('div');
            imageModal.id = 'image-preview-modal';
            imageModal.className = 'modal fade';
            imageModal.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Image Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img id="preview-image" src="" alt="" class="img-fluid" style="max-height: 70vh;">
                        </div>
                        <div class="modal-footer">
                            <span id="image-filename" class="me-auto text-muted"></span>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(imageModal);
        }

        // Update modal content
        const previewImage = imageModal.querySelector('#preview-image');
        const filenameSpan = imageModal.querySelector('#image-filename');
        previewImage.src = imageUrl;
        previewImage.alt = fileName;
        filenameSpan.textContent = fileName;

        // Show modal (using jQuery if Bootstrap 4, or native Bootstrap 5)
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(imageModal);
            modal.show();
        } else if (typeof $ !== 'undefined') {
            $(imageModal).modal('show');
        }
    }

    showPdfModal(pdfUrl, fileName) {
        let pdfModal = document.getElementById('pdf-preview-modal');
        if (!pdfModal) {
            pdfModal = document.createElement('div');
            pdfModal.id = 'pdf-preview-modal';
            pdfModal.className = 'modal fade';
            pdfModal.innerHTML = `
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">PDF Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <iframe id="pdf-iframe" src="" style="width: 100%; height: 70vh; border: none;"></iframe>
                        </div>
                        <div class="modal-footer">
                            <span id="pdf-filename" class="me-auto text-muted"></span>
                            <a id="pdf-download" href="" download="" class="btn btn-primary me-2">
                                <i class="fas fa-download"></i> Download
                            </a>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(pdfModal);
        }

        // Update modal content
        const pdfIframe = pdfModal.querySelector('#pdf-iframe');
        const filenameSpan = pdfModal.querySelector('#pdf-filename');
        const downloadLink = pdfModal.querySelector('#pdf-download');
        
        pdfIframe.src = pdfUrl;
        filenameSpan.textContent = fileName;
        downloadLink.href = pdfUrl;
        downloadLink.download = fileName;

        // Show modal (using jQuery if Bootstrap 4, or native Bootstrap 5)
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(pdfModal);
            modal.show();
        } else if (typeof $ !== 'undefined') {
            $(pdfModal).modal('show');
        }
    }

    // Remove file method
    removeFile(element) {
        const fileItem = element.closest('.file-preview-item');
        if (fileItem) {
            const fileName = fileItem.dataset.fileName;
            
            // Clear the file input
            if (this.fileInput) {
                $(this.fileInput).val('');
            }
            
            // Remove preview
            fileItem.remove();
            
            // Reset drop zone state
            if (this.dropZone) {
                $(this.dropZone).removeClass('has-files');
            }
            
            // Trigger change event
            if (this.fileInput) {
                $(this.fileInput).trigger('change');
            }
        }
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize enhanced file upload for forms with file inputs
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        const form = input.closest('form');
        if (form && !form.dataset.enhancedUpload) {
            // Get configuration from data attributes or use defaults
            const maxFileSize = parseInt(input.dataset.maxSize) * 1024 || 10 * 1024 * 1024; // Convert KB to bytes
            const allowedTypes = input.accept ? 
                input.accept.split(',').map(type => type.trim().replace('.', '')) : 
                ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

            const uploader = new EnhancedFileUpload({
                maxFileSize: maxFileSize,
                allowedTypes: allowedTypes,
                fileInput: input,
                dropZone: form.querySelector('.drop-zone') || form,
                previewContainer: form.querySelector('.file-preview'),
                progressContainer: form.querySelector('.upload-progress')
            });

            // Mark form as enhanced to prevent double initialization
            form.dataset.enhancedUpload = 'true';

            // Handle form submission with progress
            form.addEventListener('submit', function(e) {
                if (uploader.files.length > 0) {
                    e.preventDefault();
                    return uploader.handleFormSubmission(form);
                }
            });
        }
    });
});

// Export for manual initialization
window.EnhancedFileUpload = EnhancedFileUpload;