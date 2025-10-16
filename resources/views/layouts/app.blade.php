<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Pushp Niketan School - School Management System')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Critical CSS -->
    @if(config('assets.optimization.critical_css'))
        <style>
            {!! asset_manager()->getCriticalCSS() !!}
        </style>
    @endif

    <!-- Preload Critical Assets -->
    @if(config('assets.optimization.preload.enabled'))
        {!! preload_asset('css/app.css', 'style') !!}
        {!! preload_asset('js/app.js', 'script') !!}
    @endif

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Moment.js for time formatting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    
    <!-- Custom App CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    
    <!-- Attendance System CSS -->
    <link href="{{ asset('css/attendance-mobile.css') }}" rel="stylesheet">
    <link href="{{ asset('css/attendance-loading.css') }}" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --info-color: #0891b2;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8fafc;
        }

        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color) !important;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }

        .main-content {
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%) !important;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background-color: #f8fafc;
            border: none;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .alert {
            border: none;
            border-radius: 8px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .breadcrumb {
            background: none;
            padding: 0;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: var(--secondary-color);
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .footer {
            background-color: #1f2937;
            color: #9ca3af;
            padding: 20px 0;
            margin-top: 40px;
        }
    </style>

    @stack('styles')
</head>
<body>
    <div id="app">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    <img src="{{ asset('images/school-logo.svg') }}" alt="PNS Logo" style="height: 40px; width: 40px; margin-right: 10px;">
                    <div>
                        <strong style="color: #1e40af; font-size: 1.1rem;">Pushp Niketan School</strong>
                        <div style="font-size: 0.75rem; color: #64748b; line-height: 1;">Dhampur</div>
                    </div>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">
                                    <i class="fas fa-sign-in-alt me-1"></i>Login
                                </a>
                            </li>
                        @else
                            <!-- Notifications Dropdown -->
                            <li class="nav-item dropdown me-3">
                                <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" id="notificationDropdown">
                                    <i class="fas fa-bell fs-5"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                                        0
                                    </span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
                                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Notifications</span>
                                        <button class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()">Mark All Read</button>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <div id="notificationList">
                                        <div class="text-center py-3 text-muted">
                                            <i class="fas fa-bell-slash"></i>
                                            <p class="mb-0">No notifications</p>
                                        </div>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <div class="dropdown-footer text-center">
                                        <a href="#" class="btn btn-sm btn-primary">View All Notifications</a>
                                    </div>
                                </div>
                            </li>

                            <!-- User Dropdown -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                    <div class="avatar bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="row">
                @auth
                <!-- Sidebar -->
                <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}" href="{{ route('dashboard.redirect') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('class-teacher-permissions.*') ? 'active' : '' }}" href="{{ route('class-teacher-permissions.index') }}">
                                    <i class="fas fa-user-shield me-2"></i>Permissions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('sr-register.*') ? 'active' : '' }}" href="{{ route('sr-register.index') }}">
                                    <i class="fas fa-book me-2"></i>SR Register
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('biometric-attendance.*') ? 'active' : '' }}" href="{{ route('biometric-attendance.index') }}">
                                    <i class="fas fa-fingerprint me-2"></i>Attendance
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('exam-papers.*') ? 'active' : '' }}" href="{{ route('exam-papers.index') }}">
                                    <i class="fas fa-file-alt me-2"></i>Exam Papers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admit-cards.*') ? 'active' : '' }}" href="#">
                                    <i class="fas fa-id-card"></i> Admit Cards
                                </a>
                            </li>
                            @if(auth()->user() && auth()->user()->hasPermission('view-class-audit'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('class-data-audit.*') ? 'active' : '' }}" href="{{ route('class-data-audit.index') }}">
                                    <i class="fas fa-clipboard-list me-2"></i>Class Data Audit
                                </a>
                            </li>
                            @endif
                            @can('teacher-access')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('teacher-documents.*') ? 'active' : '' }}" href="{{ route('teacher-documents.index') }}">
                                    <i class="fas fa-folder-open me-2"></i>My Documents
                                </a>
                            </li>
                            @endcan
                            
                            @can('admin-access')
                            <li class="nav-item mt-3">
                                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                                    <span>Administration</span>
                                </h6>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                    <i class="fas fa-users me-2"></i>Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('classes.*') ? 'active' : '' }}" href="{{ route('classes.index') }}">
                                    <i class="fas fa-chalkboard me-2"></i>Classes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('subjects.*') ? 'active' : '' }}" href="{{ route('subjects.index') }}">
                                    <i class="fas fa-book-open me-2"></i>Subjects
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('teacher-documents.admin.*') ? 'active' : '' }}" href="{{ route('teacher-documents.admin.index') }}">
                                    <i class="fas fa-file-check me-2"></i>Teacher Documents
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('maintenance.*') ? 'active' : '' }}" href="{{ route('maintenance.index') }}">
                                    <i class="fas fa-tools me-2"></i>System Maintenance
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </nav>

                <!-- Main content -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                @else
                <main class="col-12 main-content">
                @endauth
                    <!-- Breadcrumb -->
                    @if(isset($breadcrumbs))
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            @foreach($breadcrumbs as $breadcrumb)
                                @if($loop->last)
                                    <li class="breadcrumb-item active">{{ $breadcrumb['title'] }}</li>
                                @else
                                    <li class="breadcrumb-item">
                                        <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a>
                                    </li>
                                @endif
                            @endforeach
                        </ol>
                    </nav>
                    @endif

                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>{{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Page Content -->
                    @yield('content')
                </main>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; {{ date('Y') }} PNS Dhampur School Management System. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0">Powered by Laravel {{ app()->version() }}</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        // Fallback: if CDN jQuery fails, try local copy
        (function() {
            if (!window.jQuery) {
                var s = document.createElement('script');
                s.src = '{{ asset('vendor/jquery/jquery-3.7.0.min.js') }}';
                s.async = true;
                document.head.appendChild(s);
            }
        })();
    </script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom App JS -->
    <script src="{{ asset('js/app.js') }}"></script>
    
    <!-- Validation System -->
    <script src="{{ asset('js/validation.js') }}"></script>
    
    <!-- Global AJAX CSRF Setup -->
    <script>
        (function() {
            var csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrfTokenEl ? csrfTokenEl.getAttribute('content') : null;

            if (window.jQuery) {
                // Setup CSRF token for all AJAX requests (jQuery)
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                // Initialize validation system when document is ready
                $(function() {
                    if (window.validationSystem) {
                        // Add custom validation rules for the school management system
                        window.validationSystem.addValidator('indian_phone', function(value) {
                            if (!value) return true;
                            const cleaned = value.replace(/\D/g, '');
                            return /^[6-9]\d{9}$/.test(cleaned);
                        }, 'Please enter a valid Indian mobile number (10 digits starting with 6-9).');

                        window.validationSystem.addValidator('academic_year', function(value) {
                            if (!value) return true;
                            return /^\d{4}-\d{4}$/.test(value);
                        }, 'Please enter academic year in format YYYY-YYYY (e.g., 2023-2024).');

                        window.validationSystem.addValidator('roll_number', function(value) {
                            if (!value) return true;
                            return /^[A-Z0-9]{1,10}$/.test(value);
                        }, 'Roll number must contain only uppercase letters and numbers (max 10 characters).');

                        window.validationSystem.addValidator('admission_number', function(value) {
                            if (!value) return true;
                            return /^ADM\d{4,8}$/.test(value);
                        }, 'Admission number must start with "ADM" followed by 4-8 digits.');

                        window.validationSystem.addValidator('class_section', function(value) {
                            if (!value) return true;
                            return /^(1[0-2]|[1-9])-[A-Z]$/.test(value);
                        }, 'Class section must be in format like "10-A", "12-B", etc.');

                        // Initialize all forms with validation
                        $('form[data-validate="true"]').each(function() {
                            window.validationSystem.initializeForm(this);
                        });
                    }
                });
            } else {
                // Minimal fallback: expose CSRF token for fetch APIs
                window.CSRF_TOKEN = csrfToken;
            }
        })();
    </script>
    
    <!-- Attendance System JavaScript -->
    <script src="{{ asset('js/attendance-notifications.js') }}"></script>
    <script src="{{ asset('js/attendance-validation.js') }}"></script>
    <script src="{{ asset('js/attendance-accessibility.js') }}"></script>
    <script src="{{ asset('js/attendance-performance.js') }}"></script>
    <script src="{{ asset('js/attendance-loading.js') }}"></script>
    @if(config('app.debug'))
    <script src="{{ asset('js/attendance-integration-test.js') }}"></script>
    @endif
    <script>
        // Auto-hide alerts after 5 seconds (works without jQuery)
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(el) {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(function() { el.style.display = 'none'; }, 600);
            });
        }, 5000);

        // Sidebar toggle for mobile (vanilla JS)
        function toggleSidebar() {
            var sidebar = document.querySelector('.sidebar');
            if (sidebar) sidebar.classList.toggle('show');
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Notification System
        @auth
        if (window.jQuery) {
            let notificationPollingInterval;
            let isLoadingNotifications = false;
            let isMarkingAllRead = false;

            $(function() {
                loadNotifications();
                startNotificationPolling();
            });

            function loadNotifications() {
                if (isLoadingNotifications) return;
                isLoadingNotifications = true;
                $.ajax({
                    url: '#',
                    method: 'GET',
                    success: function(response) {
                        if (response && response.success) {
                            updateNotificationDropdown(response.notifications || []);
                            updateNotificationBadge(response.unread_count || 0);
                        }
                    },
                    complete: function() {
                        isLoadingNotifications = false;
                    }
                });
            }

            function updateNotificationDropdown(notifications) {
                const notificationList = $('#notificationList');
                if (!notificationList.length) return;
                
                if (!notifications || notifications.length === 0) {
                    notificationList.html(
                        '<div class="text-center py-3 text-muted">\n' +
                        '  <i class="fas fa-bell-slash"></i>\n' +
                        '  <p class="mb-0">No notifications</p>\n' +
                        '</div>'
                    );
                    return;
                }

                let html = '';
                notifications.forEach(function(notification) {
                    const isRead = notification.is_read;
                    const timeAgo = window.moment ? moment(notification.created_at).fromNow() : '';
                    
                    html += (
                        '<div class="dropdown-item notification-item ' + (isRead ? '' : 'unread') + '" data-id="' + notification.id + '">' +
                        '  <div class="d-flex">' +
                        '    <div class="flex-shrink-0">' +
                        '      <i class="fas ' + getNotificationIcon(notification.type) + ' text-primary"></i>' +
                        '    </div>' +
                        '    <div class="flex-grow-1 ms-2">' +
                        '      <h6 class="mb-1 fw-bold">' + (notification.title || '') + '</h6>' +
                        '      <p class="mb-1 small text-muted">' + (notification.message || '') + '</p>' +
                        '      <small class="text-muted">' + timeAgo + '</small>' +
                        '    </div>' +
                        '    <div class="flex-shrink-0">' + (isRead ? '' : '<span class="badge bg-primary rounded-pill">New</span>') + '</div>' +
                        '  </div>' +
                        '</div>'
                    );
                });
                
                notificationList.html(html);
            }

            function updateNotificationBadge(count) {
                const badge = $('#notificationBadge');
                if (!badge.length) return;
                if (count > 0) {
                    badge.text(count > 99 ? '99+' : count).show();
                } else {
                    badge.hide();
                }
            }

            function getNotificationIcon(type) {
                const icons = {
                    'assignment_deadline': 'fa-clock',
                    'assignment_created': 'fa-plus-circle',
                    'assignment_graded': 'fa-check-circle',
                    'syllabus_uploaded': 'fa-file-upload',
                    'system_announcement': 'fa-bullhorn'
                };
                return icons[type] || 'fa-bell';
            }

            function markAllAsRead() {
                if (isMarkingAllRead) return;
                isMarkingAllRead = true;
                $.ajax({
                    url: '#',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response && response.success) {
                            loadNotifications();
                        }
                    },
                    complete: function() {
                        isMarkingAllRead = false;
                    }
                });
            }

            function startNotificationPolling() {
                notificationPollingInterval = setInterval(function() {
                    loadNotifications();
                }, 30000); // Poll every 30 seconds; in-flight guard prevents overlap
            }

            // Handle notification item clicks
            $(document).on('click', '.notification-item', function() {
                const notificationId = $(this).data('id');
                
                // Mark as read if unread
                if ($(this).hasClass('unread')) {
                    // Prevent rapid double clicks from firing duplicate requests
                    if ($(this).data('marking')) return;
                    $(this).data('marking', true);
                    $.ajax({
                        url: '#',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function() {
                            loadNotifications();
                        },
                        complete: () => {
                            $(this).data('marking', false);
                        }
                    });
                }
            });
        }
        @endauth
    </script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('scripts')
</body>
</html>