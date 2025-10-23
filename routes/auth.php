<?php

use App\Http\Controllers\Auth\NewAuthController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\UserRoleController as AdminUserRoleController;
// Use Kernel aliases for middleware to ensure consistency
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Here are the authentication routes for the new role-based system.
| These routes handle login, logout, password management, and user
| authentication with proper security measures and role-based access.
|
*/

// Guest routes (unauthenticated users only)
Route::middleware('guest')->group(function () {
    
    // Login routes
    Route::get('/login', [NewAuthController::class, 'showLoginForm'])
         ->name('login');
    
    Route::post('/login', [NewAuthController::class, 'login'])
         ->name('login.submit');

    // Password reset routes
    Route::get('/forgot-password', [NewAuthController::class, 'showPasswordResetForm'])
         ->name('password.request');
    
    Route::post('/forgot-password', [NewAuthController::class, 'sendPasswordResetLink'])
         ->name('password.email');
    
    Route::get('/reset-password/{token}', [NewAuthController::class, 'showPasswordResetFormWithToken'])
         ->name('password.reset');
    
    Route::post('/reset-password', [NewAuthController::class, 'resetPassword'])
         ->name('password.update');
});

// Authenticated routes
Route::middleware(['auth', 'session.security'])->group(function () {
    
    // Logout route
    Route::post('/logout', [NewAuthController::class, 'logout'])
         ->name('logout');
    
    // Password change routes (accessible to all authenticated users)
    Route::get('/change-password', [NewAuthController::class, 'showPasswordChangeForm'])
         ->name('password.change.form');
    
    Route::post('/change-password', [NewAuthController::class, 'changePassword'])
         ->name('password.change');

    // User info API routes
    Route::get('/api/user', [NewAuthController::class, 'user'])
         ->name('api.user');
    
    Route::get('/api/auth/check', [NewAuthController::class, 'check'])
         ->name('api.auth.check');

    // Dashboard routes with role-based access
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        
        // Default dashboard (accessible to all authenticated users)
        Route::get('/', function () {
            return redirect()->route('dashboard.redirect');
        })->name('default');

        // Dashboard redirect based on role
        Route::get('/redirect', function () {
            $user = auth()->user();
            $primaryRole = $user->getPrimaryRole();
            
            if (!$primaryRole) {
                return view('dashboard.no-role');
            }

            switch ($primaryRole->name) {
                case 'super_admin':
                    return redirect()->route('dashboard.super-admin');
                case 'admin':
                    return redirect()->route('dashboard.admin');
                case 'principal':
                    return redirect()->route('dashboard.principal');
                case 'teacher':
                    return redirect()->route('dashboard.teacher');
                case 'student':
                    return redirect()->route('dashboard.student');
                case 'parent':
                    return redirect()->route('dashboard.parent');
                default:
                    return view('dashboard');
            }
        })->name('redirect');

        // Super Admin Dashboard
        Route::get('/super-admin', [\App\Http\Controllers\DashboardController::class, 'superAdmin'])
            ->middleware('role:super_admin')
            ->name('super-admin');

        // Admin Dashboard
        Route::get('/admin', function () {
            return view('dashboard.admin');
        })->middleware('role:admin,super_admin')
          ->name('admin');

        // Principal Dashboard
        Route::get('/principal', function () {
            return view('dashboard.principal');
        })->middleware('role:principal,admin,super_admin')
          ->name('principal');

        // Teacher Dashboard
        Route::get('/teacher', function () {
            return view('dashboard.teacher');
        })->middleware('role:teacher,principal,admin,super_admin')
          ->name('teacher');

        // Student Dashboard
        Route::get('/student', function () {
            return view('dashboard.student');
        })->middleware('role:student')
          ->name('student');

        // Parent Dashboard
        Route::get('/parent', function () {
            return view('dashboard.parent');
        })->middleware('role:parent')
          ->name('parent');
    });

    // User management routes (permission-based)
    Route::prefix('users')->name('ui.users.')->group(function () {
        
        Route::get('/', function () {
            return view('users.index');
        })->middleware('permission:users.view_all')
          ->name('index');

        Route::get('/create', function () {
            return view('users.create');
        })->middleware('permission:users.create')
          ->name('create');

        Route::get('/{user}', function ($user) {
            return view('users.show', compact('user'));
        })->middleware('permission:users.view')
          ->name('show');

        Route::get('/{user}/edit', function ($user) {
            return view('users.edit', compact('user'));
        })->middleware('permission:users.edit')
          ->name('edit');

        Route::delete('/{user}', function ($user) {
            // User deletion logic would go here
            return redirect()->route('ui.users.index');
        })->middleware('permission:users.delete')
          ->name('destroy');
    });

    // Role management routes (permission-based)
    Route::prefix('roles')->name('roles.')->group(function () {
        
        Route::get('/', function () {
            return view('roles.index');
        })->middleware('permission:roles.view_all')
          ->name('index');

        Route::get('/create', function () {
            return view('roles.create');
        })->middleware('permission:roles.create')
          ->name('create');

        Route::get('/{role}/edit', function ($role) {
            return view('roles.edit', compact('role'));
        })->middleware('permission:roles.edit')
          ->name('edit');

        Route::delete('/{role}', function ($role) {
            // Role deletion logic would go here
            return redirect()->route('roles.index');
        })->middleware('permission:roles.delete')
          ->name('destroy');
    });

    // Permission management routes (super admin only)
    Route::prefix('permissions')->name('permissions.')->group(function () {
        
        Route::get('/', function () {
            return view('permissions.index');
        })->middleware('permission:permissions.view_all')
          ->name('index');

        Route::get('/manage', function () {
            return view('permissions.manage');
        })->middleware('permission:permissions.manage')
          ->name('manage');
    });

    // School management routes
    Route::prefix('schools')->name('schools.')->group(function () {
        
        Route::get('/', function () {
            return view('schools.index');
        })->middleware('permission:schools.view_all')
          ->name('index');

        Route::get('/create', function () {
            return view('schools.create');
        })->middleware('permission:schools.create')
          ->name('create');

        Route::get('/{school}', function ($school) {
            return view('schools.show', compact('school'));
        })->middleware('permission:schools.view')
          ->name('show');

        Route::get('/{school}/edit', function ($school) {
            return view('schools.edit', compact('school'));
        })->middleware('permission:schools.edit')
          ->name('edit');
    });

    // System settings routes (admin and super admin only)
    Route::prefix('system')->name('system.')->group(function () {
        
        Route::get('/settings', function () {
            return view('system.settings');
        })->middleware('permission:system.settings.view')
          ->name('settings');

        Route::get('/backup', function () {
            return view('system.backup');
        })->middleware('permission:system.backup')
          ->name('backup');

        Route::get('/logs', function () {
            return view('system.logs');
        })->middleware('permission:system.logs.view')
          ->name('logs');

        Route::get('/audit', function () {
            return view('system.audit');
        })->middleware('permission:audit.view')
          ->name('audit');
    });

    // Reports routes (role-based access)
    Route::prefix('reports')->name('reports.')->group(function () {
        
        Route::get('/', function () {
            return view('reports.index');
        })->middleware(RoleBasedAccess::class . ':teacher,principal,admin,super_admin')
          ->name('index');

        Route::get('/attendance', function () {
            return view('reports.attendance');
        })->middleware(PermissionCheck::class . ':reports.attendance')
          ->name('attendance');

        Route::get('/academic', function () {
            return view('reports.academic');
        })->middleware(PermissionCheck::class . ':reports.academic')
          ->name('academic');

        Route::get('/financial', function () {
            return view('reports.financial');
        })->middleware(PermissionCheck::class . ':reports.financial')
          ->name('financial');
    });

    // Profile management routes (accessible to all authenticated users)
    Route::prefix('profile')->name('profile.')->group(function () {
        
        Route::get('/', function () {
            return view('profile.show');
        })->name('show');

        Route::get('/edit', function () {
            return view('profile.edit');
        })->name('edit');

        Route::get('/security', function () {
            return view('profile.security');
        })->name('security');

        Route::get('/sessions', function () {
            return view('profile.sessions');
        })->name('sessions');
    });

    // Admin management routes (controller-driven, RBAC-enforced via controller middleware)
    Route::prefix('admin')->name('admin.')->group(function () {
        // Users
        Route::resource('users', AdminUserController::class);

        // Roles
        Route::resource('roles', AdminRoleController::class);

        // Permissions
        Route::resource('permissions', AdminPermissionController::class);

        // Permission utilities
        Route::get('permissions/export', [AdminPermissionController::class, 'export'])
            ->name('permissions.export');
        Route::post('permissions/import', [AdminPermissionController::class, 'import'])
            ->name('permissions.import');
        Route::post('permissions/defaults', [AdminPermissionController::class, 'createDefaults'])
            ->name('permissions.create-defaults');

        // User role assignment routes
        Route::get('users/{user}/roles', [AdminUserRoleController::class, 'show'])
            ->name('users.roles.show');
        Route::post('users/{user}/roles', [AdminUserRoleController::class, 'assign'])
            ->name('users.roles.assign');
        Route::put('users/{user}/roles/{assignment}', [AdminUserRoleController::class, 'update'])
            ->name('users.roles.update');
        Route::delete('users/{user}/roles/{assignment}', [AdminUserRoleController::class, 'remove'])
            ->name('users.roles.remove');

        // AJAX role assignment operations
        Route::post('users/{user}/roles/{assignment}/primary', [AdminUserRoleController::class, 'makePrimary'])
            ->name('users.roles.make-primary');
        Route::post('users/{user}/roles/{assignment}/extend', [AdminUserRoleController::class, 'extend'])
            ->name('users.roles.extend');
        Route::post('users/{user}/roles/{assignment}/make-permanent', [AdminUserRoleController::class, 'makePermanent'])
            ->name('users.roles.make-permanent');
        Route::post('users/{user}/roles/{assignment}/reactivate', [AdminUserRoleController::class, 'reactivate'])
            ->name('users.roles.reactivate');
    });
});

// API routes for AJAX requests
Route::prefix('api')->name('api.')->middleware(['auth', 'session.security'])->group(function () {
    
    // User management API
    Route::apiResource('users', 'Api\UserController')
         ->middleware('permission:users.view_all,users.create,users.edit,users.delete');

    // Role management API
    Route::apiResource('roles', 'Api\RoleController')
         ->middleware('permission:roles.view_all,roles.create,roles.edit,roles.delete');

    // Permission management API
    Route::get('/permissions', 'Api\PermissionController@index')
         ->middleware('permission:permissions.view_all');

    // School management API
    Route::apiResource('schools', 'Api\SchoolController')
         ->middleware('permission:schools.view_all,schools.create,schools.edit,schools.delete');

    // Session management API
    Route::get('/sessions', 'Api\SessionController@index')
         ->name('api.sessions.index');
    
    Route::delete('/sessions/{session}', 'Api\SessionController@destroy')
         ->name('api.sessions.destroy');

    // Activity logs API
    Route::get('/activities', 'Api\ActivityController@index')
         ->middleware(PermissionCheck::class . ':audit.view')
         ->name('api.activities.index');
});

// Fallback route for undefined routes (authenticated users)
Route::fallback(function () {
    if (auth()->check()) {
        return redirect()->route('dashboard.default')
                        ->with('error', 'Page not found.');
    }
    
    return redirect()->route('login')
                    ->with('error', 'Page not found. Please log in.');
});