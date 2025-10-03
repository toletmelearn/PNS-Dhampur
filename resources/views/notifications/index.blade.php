@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-bell mr-2"></i>
                        Notifications
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-primary" id="markAllReadBtn">
                            <i class="fas fa-check-double"></i> Mark All Read
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" id="deleteAllReadBtn">
                            <i class="fas fa-trash"></i> Delete All Read
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select class="form-control" id="typeFilter">
                                <option value="">All Types</option>
                                <option value="assignment_deadline">Assignment Deadline</option>
                                <option value="assignment_created">Assignment Created</option>
                                <option value="assignment_graded">Assignment Graded</option>
                                <option value="syllabus_uploaded">Syllabus Uploaded</option>
                                <option value="system_announcement">System Announcement</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="unread">Unread</option>
                                <option value="read">Read</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="priorityFilter">
                                <option value="">All Priorities</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary" id="applyFilters">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>

                    <!-- Notifications List -->
                    <div id="notificationsList">
                        <!-- Notifications will be loaded here via AJAX -->
                    </div>

                    <!-- Pagination -->
                    <div id="paginationContainer" class="d-flex justify-content-center mt-3">
                        <!-- Pagination will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Detail Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalTitle">Notification Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="notificationModalBody">
                <!-- Notification details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="markAsReadBtn">Mark as Read</button>
                <button type="button" class="btn btn-danger" id="deleteNotificationBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.notification-item {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.notification-item:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transform: translateY(-1px);
}

.notification-item.unread {
    border-left: 4px solid #007bff;
    background-color: #f8f9fc;
}

.notification-item.read {
    border-left: 4px solid #6c757d;
    background-color: #ffffff;
}

.notification-priority-high {
    border-left-color: #dc3545 !important;
}

.notification-priority-medium {
    border-left-color: #ffc107 !important;
}

.notification-priority-low {
    border-left-color: #28a745 !important;
}

.notification-type-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.notification-actions {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.notification-item:hover .notification-actions {
    opacity: 1;
}

.notification-time {
    font-size: 0.875rem;
    color: #6c757d;
}

.notification-content {
    cursor: pointer;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentNotificationId = null;

    // Load notifications on page load
    loadNotifications();

    // Apply filters
    $('#applyFilters').click(function() {
        currentPage = 1;
        loadNotifications();
    });

    // Mark all as read
    $('#markAllReadBtn').click(function() {
        if (confirm('Are you sure you want to mark all notifications as read?')) {
            $.ajax({
                url: '{{ route("learning.notifications.mark-all-read") }}',
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    toastr.success('All notifications marked as read');
                    loadNotifications();
                    updateNotificationCount();
                },
                error: function() {
                    toastr.error('Error marking notifications as read');
                }
            });
        }
    });

    // Delete all read notifications
    $('#deleteAllReadBtn').click(function() {
        if (confirm('Are you sure you want to delete all read notifications? This action cannot be undone.')) {
            $.ajax({
                url: '{{ route("learning.notifications.delete-all-read") }}',
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    toastr.success('All read notifications deleted');
                    loadNotifications();
                    updateNotificationCount();
                },
                error: function() {
                    toastr.error('Error deleting notifications');
                }
            });
        }
    });

    // Load notifications function
    function loadNotifications() {
        const filters = {
            type: $('#typeFilter').val(),
            status: $('#statusFilter').val(),
            priority: $('#priorityFilter').val(),
            page: currentPage
        };

        $.ajax({
            url: '{{ route("learning.notifications.index") }}',
            method: 'GET',
            data: filters,
            success: function(response) {
                renderNotifications(response.notifications);
                renderPagination(response.pagination);
            },
            error: function() {
                toastr.error('Error loading notifications');
            }
        });
    }

    // Render notifications
    function renderNotifications(notifications) {
        let html = '';
        
        if (notifications.length === 0) {
            html = '<div class="text-center py-4"><i class="fas fa-bell-slash fa-3x text-muted mb-3"></i><p class="text-muted">No notifications found</p></div>';
        } else {
            notifications.forEach(function(notification) {
                const isRead = notification.is_read;
                const priorityClass = `notification-priority-${notification.priority}`;
                const typeClass = getTypeClass(notification.type);
                
                html += `
                    <div class="notification-item ${isRead ? 'read' : 'unread'} ${priorityClass}" data-id="${notification.id}">
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="notification-content flex-grow-1" onclick="showNotificationDetails(${notification.id})">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge ${typeClass} notification-type-badge mr-2">
                                            ${getTypeLabel(notification.type)}
                                        </span>
                                        <span class="badge badge-${getPriorityColor(notification.priority)} notification-type-badge">
                                            ${notification.priority.toUpperCase()}
                                        </span>
                                        ${!isRead ? '<span class="badge badge-primary notification-type-badge ml-2">NEW</span>' : ''}
                                    </div>
                                    <h6 class="mb-1">${notification.title}</h6>
                                    <p class="mb-1 text-muted">${notification.message}</p>
                                    <small class="notification-time">
                                        <i class="fas fa-clock mr-1"></i>
                                        ${formatDate(notification.created_at)}
                                    </small>
                                </div>
                                <div class="notification-actions ml-3">
                                    <div class="btn-group btn-group-sm">
                                        ${!isRead ? 
                                            `<button class="btn btn-outline-primary" onclick="markAsRead(${notification.id})" title="Mark as Read">
                                                <i class="fas fa-check"></i>
                                            </button>` : 
                                            `<button class="btn btn-outline-secondary" onclick="markAsUnread(${notification.id})" title="Mark as Unread">
                                                <i class="fas fa-undo"></i>
                                            </button>`
                                        }
                                        <button class="btn btn-outline-danger" onclick="deleteNotification(${notification.id})" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#notificationsList').html(html);
    }

    // Render pagination
    function renderPagination(pagination) {
        let html = '';
        
        if (pagination.last_page > 1) {
            html += '<nav><ul class="pagination">';
            
            // Previous button
            if (pagination.current_page > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">Previous</a></li>`;
            }
            
            // Page numbers
            for (let i = 1; i <= pagination.last_page; i++) {
                const active = i === pagination.current_page ? 'active' : '';
                html += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
            }
            
            // Next button
            if (pagination.current_page < pagination.last_page) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">Next</a></li>`;
            }
            
            html += '</ul></nav>';
        }
        
        $('#paginationContainer').html(html);
    }

    // Helper functions
    function getTypeClass(type) {
        const classes = {
            'assignment_deadline': 'badge-warning',
            'assignment_created': 'badge-info',
            'assignment_graded': 'badge-success',
            'syllabus_uploaded': 'badge-primary',
            'system_announcement': 'badge-danger'
        };
        return classes[type] || 'badge-secondary';
    }

    function getTypeLabel(type) {
        const labels = {
            'assignment_deadline': 'Deadline',
            'assignment_created': 'New Assignment',
            'assignment_graded': 'Graded',
            'syllabus_uploaded': 'Syllabus',
            'system_announcement': 'Announcement'
        };
        return labels[type] || type;
    }

    function getPriorityColor(priority) {
        const colors = {
            'high': 'danger',
            'medium': 'warning',
            'low': 'success'
        };
        return colors[priority] || 'secondary';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        return date.toLocaleDateString();
    }

    // Global functions
    window.changePage = function(page) {
        currentPage = page;
        loadNotifications();
    };

    window.markAsRead = function(id) {
        $.ajax({
            url: `{{ route("learning.notifications.index") }}/${id}/read`,
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                loadNotifications();
                updateNotificationCount();
            }
        });
    };

    window.markAsUnread = function(id) {
        $.ajax({
            url: `{{ route("learning.notifications.index") }}/${id}/unread`,
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                loadNotifications();
                updateNotificationCount();
            }
        });
    };

    window.deleteNotification = function(id) {
        if (confirm('Are you sure you want to delete this notification?')) {
            $.ajax({
                url: `{{ route("learning.notifications.index") }}/${id}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    loadNotifications();
                    updateNotificationCount();
                    toastr.success('Notification deleted');
                }
            });
        }
    };

    window.showNotificationDetails = function(id) {
        currentNotificationId = id;
        $.ajax({
            url: `{{ route("learning.notifications.index") }}/${id}`,
            method: 'GET',
            success: function(notification) {
                $('#notificationModalTitle').text(notification.title);
                
                let modalBody = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Type:</strong> ${getTypeLabel(notification.type)}
                        </div>
                        <div class="col-md-6">
                            <strong>Priority:</strong> <span class="badge badge-${getPriorityColor(notification.priority)}">${notification.priority.toUpperCase()}</span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Status:</strong> ${notification.is_read ? 'Read' : 'Unread'}
                        </div>
                        <div class="col-md-6">
                            <strong>Created:</strong> ${formatDate(notification.created_at)}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <strong>Message:</strong>
                            <p class="mt-2">${notification.message}</p>
                        </div>
                    </div>
                `;
                
                if (notification.data) {
                    modalBody += `
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <strong>Additional Information:</strong>
                                <pre class="mt-2 bg-light p-2 rounded">${JSON.stringify(notification.data, null, 2)}</pre>
                            </div>
                        </div>
                    `;
                }
                
                $('#notificationModalBody').html(modalBody);
                $('#notificationModal').modal('show');
            }
        });
    };

    // Modal actions
    $('#markAsReadBtn').click(function() {
        if (currentNotificationId) {
            markAsRead(currentNotificationId);
            $('#notificationModal').modal('hide');
        }
    });

    $('#deleteNotificationBtn').click(function() {
        if (currentNotificationId) {
            deleteNotification(currentNotificationId);
            $('#notificationModal').modal('hide');
        }
    });

    // Update notification count in navbar
    function updateNotificationCount() {
        $.ajax({
            url: '{{ route("learning.notifications.unread-count") }}',
            method: 'GET',
            success: function(response) {
                $('.notification-count').text(response.count);
                if (response.count > 0) {
                    $('.notification-count').show();
                } else {
                    $('.notification-count').hide();
                }
            }
        });
    }
});
</script>
@endpush