<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class NewUser extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'username',
        'phone',
        'employee_id',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'must_change_password',
        'password_never_expires',
        'password_changed_at',
        'password_expires_at',
        'password_reset_required',
        'failed_login_attempts',
        'account_locked_at',
        'account_locked_reason',
        'locked_until',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'two_factor_enabled',
        'last_login_at',
        'last_login_ip',
        'last_login_user_agent',
        'login_count',
        'status',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
        'timezone',
        'language',
        'preferences'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'password_expires_at' => 'datetime',
        'account_locked_at' => 'datetime',
        'locked_until' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'must_change_password' => 'boolean',
        'password_never_expires' => 'boolean',
        'password_reset_required' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'is_active' => 'boolean',
        'failed_login_attempts' => 'integer',
        'login_count' => 'integer',
        'two_factor_recovery_codes' => 'array',
        'preferences' => 'array',
        'deleted_at'
    ];

    // User status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PENDING = 'pending';
    const STATUS_PENDING_VERIFICATION = 'pending_verification';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_LOCKED = 'locked';

    // Account lock reasons
    const LOCK_REASON_FAILED_ATTEMPTS = 'failed_attempts';
    const LOCK_REASON_ADMIN_ACTION = 'admin_action';
    const LOCK_REASON_SECURITY_BREACH = 'security_breach';
    const LOCK_REASON_POLICY_VIOLATION = 'policy_violation';

    // Password policy constants
    const MAX_FAILED_ATTEMPTS = 5;
    const LOCKOUT_DURATION_MINUTES = 30;
    const PASSWORD_EXPIRY_DAYS = 90;
    const MIN_PASSWORD_LENGTH = 8;

    /**
     * Get the user's profile
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    /**
     * Get the user's role assignments
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class, 'user_id');
    }

    /**
     * Get the user's active role assignments
     */
    public function activeRoleAssignments(): HasMany
    {
        return $this->roleAssignments()->active();
    }

    /**
     * Get the user's roles through assignments
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(NewRole::class, 'user_role_assignments', 'user_id', 'role_id')
                    ->withPivot(['school_id', 'assigned_at', 'expires_at', 'is_active', 'is_primary'])
                    ->wherePivot('is_active', true)
                    ->whereNull('user_role_assignments.revoked_at')
                    ->where(function($query) {
                        $query->whereNull('user_role_assignments.expires_at')
                              ->orWhere('user_role_assignments.expires_at', '>', now());
                    });
    }

    /**
     * Get the user's permissions through roles
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
                    ->withPivot(['granted_at', 'granted_by', 'expires_at', 'is_granted'])
                    ->wherePivot('is_granted', true)
                    ->where(function($query) {
                        $query->whereNull('user_permissions.expires_at')
                              ->orWhere('user_permissions.expires_at', '>', now());
                    });
    }

    /**
     * Get user sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class, 'user_id');
    }

    /**
     * Get user activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class, 'user_id');
    }

    /**
     * Get teacher assignments (if user is a teacher)
     */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class, 'teacher_id');
    }

    /**
     * Get student enrollments (if user is a student)
     */
    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id');
    }

    /**
     * Get parent relationships (if user is a parent)
     */
    public function parentRelationships(): HasMany
    {
        return $this->hasMany(ParentStudentRelationship::class, 'parent_id');
    }

    /**
     * Get student relationships (if user is a student)
     */
    public function studentRelationships(): HasMany
    {
        return $this->hasMany(ParentStudentRelationship::class, 'student_id');
    }

    /**
     * Get the user who created this user
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    /**
     * Get the user who last updated this user
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    /**
     * Get the user who deleted this user
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by');
    }

    /**
     * Get user's display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get user's full name with role
     */
    public function getFullNameWithRoleAttribute(): string
    {
        $primaryRole = $this->getPrimaryRole();
        $roleName = $primaryRole ? $primaryRole->display_name : 'User';
        
        return "{$this->name} ({$roleName})";
    }

    /**
     * Check if user is currently active
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user account is locked
     */
    public function isLocked(): bool
    {
        if ($this->status === self::STATUS_LOCKED) {
            return true;
        }
        
        if ($this->locked_until && $this->locked_until->isFuture()) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if user account is suspended
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if user must change password
     */
    public function mustChangePassword(): bool
    {
        return $this->must_change_password || $this->password_reset_required || $this->isPasswordExpired();
    }

    /**
     * Check if password is expired
     */
    public function isPasswordExpired(): bool
    {
        if ($this->password_never_expires) {
            return false;
        }
        
        if (!$this->password_expires_at) {
            return false;
        }
        
        return $this->password_expires_at->isPast();
    }

    /**
     * Check if two-factor authentication is enabled
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && !empty($this->two_factor_secret);
    }

    /**
     * Check if email is verified
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if phone is verified
     */
    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Get user's primary role
     */
    public function getPrimaryRole(): ?NewRole
    {
        $assignment = $this->activeRoleAssignments()
                          ->where('is_primary', true)
                          ->with('role')
                          ->first();
        
        return $assignment ? $assignment->role : null;
    }

    /**
     * Get user's highest role level
     */
    public function getHighestRoleLevel(): ?int
    {
        return UserRoleAssignment::getUserHighestRoleLevel($this->id);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $roleName, $schoolId = null): bool
    {
        return UserRoleAssignment::userHasRole($this->id, $roleName, $schoolId);
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roleNames, $schoolId = null): bool
    {
        return UserRoleAssignment::userHasAnyRole($this->id, $roleNames, $schoolId);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permissionName, $schoolId = null): bool
    {
        // Check direct permissions granted to the user
        $hasDirectPermission = $this->permissions()
                                   ->where('name', $permissionName)
                                   ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        // Check role-based permissions via active roles with granted permissions
        $hasRolePermission = $this->roles()
            ->whereHas('grantedPermissions', function ($q) use ($permissionName) {
                $q->where('permissions.name', $permissionName);
            })
            ->exists();

        return $hasRolePermission;
    }

    /**
     * Check if user has any of the specified permissions
     */
    public function hasAnyPermission($permissions, $schoolId = null): bool
    {
        $permissionList = is_array($permissions) ? $permissions : [$permissions];

        // Direct permissions check
        if ($this->permissions()->whereIn('name', $permissionList)->exists()) {
            return true;
        }

        // Role-based permissions check
        return $this->roles()
            ->whereHas('grantedPermissions', function ($q) use ($permissionList) {
                $q->whereIn('permissions.name', $permissionList);
            })
            ->exists();
    }

    /**
     * Get user's active roles
     */
    public function getActiveRoles($schoolId = null): array
    {
        return UserRoleAssignment::getUserActiveRoles($this->id, $schoolId);
    }

    /**
     * Get user's permissions
     */
    public function getAllPermissions($schoolId = null): array
    {
        // Directly granted permissions
        $directPermissions = $this->permissions()->pluck('name')->toArray();

        // Role-granted permissions via active roles
        $rolePermissions = $this->roles()
            ->with(['grantedPermissions' => function ($q) {
                $q->select('permissions.id', 'permissions.name');
            }])
            ->get()
            ->flatMap(function ($role) {
                return $role->grantedPermissions->pluck('name');
            })
            ->toArray();

        return array_values(array_unique(array_merge($directPermissions, $rolePermissions)));
    }

    /**
     * Check if user can access school
     */
    public function canAccessSchool($schoolId): bool
    {
        // Super admins can access all schools
        if ($this->hasRole(NewRole::SUPER_ADMIN)) {
            return true;
        }
        
        // Check if user has any role in this school
        $roles = $this->getActiveRoles($schoolId);
        
        return !empty($roles);
    }

    /**
     * Get user's accessible schools
     */
    public function getAccessibleSchools(): array
    {
        // Super admins can access all schools
        if ($this->hasRole(NewRole::SUPER_ADMIN)) {
            return School::active()->get()->toArray();
        }
        
        $schoolIds = $this->activeRoleAssignments()
                         ->whereNotNull('school_id')
                         ->pluck('school_id')
                         ->unique()
                         ->toArray();
        
        return School::whereIn('id', $schoolIds)->active()->get()->toArray();
    }

    /**
     * Record login attempt
     */
    public function recordLoginAttempt(bool $successful, string $ipAddress = null, string $userAgent = null): void
    {
        if ($successful) {
            $this->failed_login_attempts = 0;
            $this->last_login_at = now();
            $this->last_login_ip = $ipAddress;
            $this->last_login_user_agent = $userAgent;
            $this->login_count = ($this->login_count ?? 0) + 1;
            
            // Unlock account if it was temporarily locked
            if ($this->locked_until && $this->locked_until->isPast()) {
                $this->locked_until = null;
                $this->account_locked_reason = null;
            }
        } else {
            $this->failed_login_attempts = ($this->failed_login_attempts ?? 0) + 1;
            
            // Lock account if too many failed attempts
            if ($this->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
                $this->lockAccount(self::LOCK_REASON_FAILED_ATTEMPTS);
            }
        }
        
        $this->save();
    }

    /**
     * Lock user account
     */
    public function lockAccount(string $reason = null, int $durationMinutes = null): void
    {
        $this->status = self::STATUS_LOCKED;
        $this->account_locked_at = now();
        $this->account_locked_reason = $reason ?? self::LOCK_REASON_ADMIN_ACTION;
        
        if ($durationMinutes) {
            $this->locked_until = now()->addMinutes($durationMinutes);
        } elseif ($reason === self::LOCK_REASON_FAILED_ATTEMPTS) {
            $this->locked_until = now()->addMinutes(self::LOCKOUT_DURATION_MINUTES);
        }
        
        $this->save();
        
        // Revoke all active sessions
        $this->revokeAllSessions();
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->account_locked_at = null;
        $this->account_locked_reason = null;
        $this->locked_until = null;
        $this->failed_login_attempts = 0;
        
        $this->save();
    }

    /**
     * Suspend user account
     */
    public function suspendAccount(string $reason = null): void
    {
        $this->status = self::STATUS_SUSPENDED;
        $this->account_locked_at = now();
        $this->account_locked_reason = $reason;
        
        $this->save();
        
        // Revoke all active sessions
        $this->revokeAllSessions();
    }

    /**
     * Activate user account
     */
    public function activateAccount(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->is_active = true;
        $this->account_locked_at = null;
        $this->account_locked_reason = null;
        $this->locked_until = null;
        
        $this->save();
    }

    /**
     * Change user password
     */
    public function changePassword(string $newPassword, bool $forceChange = false): void
    {
        $this->password = Hash::make($newPassword);
        $this->password_changed_at = now();
        $this->must_change_password = $forceChange;
        $this->password_reset_required = false;
        
        // Set password expiration if not set to never expire
        if (!$this->password_never_expires) {
            $this->password_expires_at = now()->addDays(self::PASSWORD_EXPIRY_DAYS);
        }
        
        $this->save();
        
        // Revoke all active sessions except current
        $this->revokeAllSessions(true);
    }

    /**
     * Force password change on next login
     */
    public function forcePasswordChange(): void
    {
        $this->must_change_password = true;
        $this->password_reset_required = true;
        $this->save();
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor(string $secret, array $recoveryCodes = []): void
    {
        $this->two_factor_secret = $secret;
        $this->two_factor_recovery_codes = $recoveryCodes;
        $this->two_factor_enabled = true;
        $this->two_factor_confirmed_at = now();
        
        $this->save();
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_enabled = false;
        $this->two_factor_confirmed_at = null;
        
        $this->save();
    }

    /**
     * Revoke all user sessions
     */
    public function revokeAllSessions(bool $exceptCurrent = false): void
    {
        $query = $this->sessions()->where('is_active', true);
        
        if ($exceptCurrent && session()->getId()) {
            $query->where('session_id', '!=', session()->getId());
        }
        
        $query->update([
            'is_active' => false,
            'logout_at' => now(),
            'logout_reason' => 'revoked_by_system'
        ]);
    }

    /**
     * Get user preferences
     */
    public function getPreference(string $key, $default = null)
    {
        $preferences = $this->preferences ?? [];
        return $preferences[$key] ?? $default;
    }

    /**
     * Set user preference
     */
    public function setPreference(string $key, $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->preferences = $preferences;
        $this->save();
    }

    /**
     * Get user's timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone ?? config('app.timezone', 'UTC');
    }

    /**
     * Get user's language
     */
    public function getLanguage(): string
    {
        return $this->language ?? config('app.locale', 'en');
    }

    /**
     * Scope: Active users only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Users by role
     */
    public function scopeWithRole($query, string $roleName)
    {
        return $query->whereHas('roleAssignments', function($q) use ($roleName) {
            $q->active()
              ->whereHas('role', function($roleQuery) use ($roleName) {
                  $roleQuery->where('name', $roleName);
              });
        });
    }

    /**
     * Scope: Users by school
     */
    public function scopeInSchool($query, $schoolId)
    {
        return $query->whereHas('roleAssignments', function($q) use ($schoolId) {
            $q->active()
              ->where(function($schoolQuery) use ($schoolId) {
                  $schoolQuery->where('school_id', $schoolId)
                             ->orWhereNull('school_id'); // Include system-wide roles
              });
        });
    }

    /**
     * Scope: Users with expired passwords
     */
    public function scopeWithExpiredPasswords($query)
    {
        return $query->where('password_never_expires', false)
                    ->where('password_expires_at', '<=', now());
    }

    /**
     * Scope: Users requiring password change
     */
    public function scopeRequiringPasswordChange($query)
    {
        return $query->where(function($q) {
            $q->where('must_change_password', true)
              ->orWhere('password_reset_required', true)
              ->orWhere(function($expiredQuery) {
                  $expiredQuery->where('password_never_expires', false)
                              ->where('password_expires_at', '<=', now());
              });
        });
    }

    /**
     * Scope: Locked users
     */
    public function scopeLocked($query)
    {
        return $query->where(function($q) {
            $q->where('status', self::STATUS_LOCKED)
              ->orWhere(function($lockedUntilQuery) {
                  $lockedUntilQuery->whereNotNull('locked_until')
                                  ->where('locked_until', '>', now());
              });
        });
    }

    /**
     * Create new user with role
     */
    public static function createWithRole(array $userData, string $roleName, $schoolId = null, array $roleData = []): self
    {
        // Set default values
        $userData['status'] = $userData['status'] ?? self::STATUS_ACTIVE;
        $userData['is_active'] = $userData['is_active'] ?? true;
        $userData['must_change_password'] = $userData['must_change_password'] ?? true;
        $userData['created_by'] = $userData['created_by'] ?? auth()->id();
        
        // Hash password if provided
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
            $userData['password_changed_at'] = now();
            
            if (!($userData['password_never_expires'] ?? false)) {
                $userData['password_expires_at'] = now()->addDays(self::PASSWORD_EXPIRY_DAYS);
            }
        }
        
        // Generate username if not provided
        if (empty($userData['username'])) {
            $userData['username'] = self::generateUsername($userData['name'], $userData['email']);
        }
        
        // Create user
        $user = self::create($userData);
        
        // Assign role
        $role = NewRole::where('name', $roleName)->first();
        if ($role) {
            UserRoleAssignment::assignRole(array_merge([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'school_id' => $schoolId,
                'is_primary' => true,
                'assigned_by' => auth()->id(),
            ], $roleData));
        }
        
        return $user;
    }

    /**
     * Generate unique username
     */
    protected static function generateUsername(string $name, string $email): string
    {
        // Try email prefix first
        $baseUsername = strtolower(explode('@', $email)[0]);
        $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
        
        // If email prefix is too short, use name
        if (strlen($baseUsername) < 3) {
            $baseUsername = strtolower(preg_replace('/[^a-z0-9]/', '', $name));
        }
        
        $username = $baseUsername;
        $counter = 1;
        
        // Ensure uniqueness
        while (self::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Get available user statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PENDING_VERIFICATION => 'Pending Verification',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_LOCKED => 'Locked',
        ];
    }

    /**
     * Get available lock reasons
     */
    public static function getLockReasons(): array
    {
        return [
            self::LOCK_REASON_FAILED_ATTEMPTS => 'Too Many Failed Login Attempts',
            self::LOCK_REASON_ADMIN_ACTION => 'Administrative Action',
            self::LOCK_REASON_SECURITY_BREACH => 'Security Breach',
            self::LOCK_REASON_POLICY_VIOLATION => 'Policy Violation',
        ];
    }

    // ===== Compatibility shims for legacy middleware =====
    public function getRoleAttribute(): string
    {
        $primaryRole = $this->getPrimaryRole();
        if ($primaryRole) {
            return $primaryRole->name;
        }
        // Fallback to legacy column if present
        if (array_key_exists('role', $this->attributes) && !empty($this->attributes['role'])) {
            return $this->attributes['role'];
        }
        return 'guest';
    }

    public function getRoleLevel(): int
    {
        $role = $this->role; // uses accessor above
        $levels = [
            'student' => 1,
            'parent' => 1,
            'teacher' => 2,
            'class_teacher' => 2,
            'exam_incharge' => 2,
            'accountant' => 3,
            'it' => 4,
            'principal' => 5,
            'admin' => 5,
            'super_admin' => 6,
        ];
        return $levels[strtolower($role)] ?? 0;
    }

    public function canAccessAttendance(): bool
    {
        $allowedRoles = [
            NewRole::SUPER_ADMIN,
            NewRole::ADMIN,
            NewRole::PRINCIPAL,
            NewRole::TEACHER,
            NewRole::STUDENT,
            NewRole::PARENT,
            'accountant', 'it', 'exam_incharge', 'class_teacher'
        ];
        if ($this->hasAnyRole($allowedRoles)) {
            return true;
        }
        // Permission fallback for granular access
        return $this->hasAnyPermission([
            'attendance.view',
            'attendance.view_all',
            'attendance.mark_all',
            'attendance.manage_settings',
        ]);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([NewRole::SUPER_ADMIN, NewRole::ADMIN, 'principal', 'it']);
    }
}