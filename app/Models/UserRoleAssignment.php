<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class UserRoleAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'role_id',
        'school_id',
        'assigned_by',
        'assigned_at',
        'expires_at',
        'is_active',
        'is_primary',
        'context_data',
        'notes',
        'revoked_at',
        'revoked_by',
        'revoke_reason'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'context_data' => 'array'
    ];

    protected $dates = [
        'assigned_at',
        'expires_at',
        'revoked_at',
        'deleted_at'
    ];

    /**
     * Get the user for this role assignment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role for this assignment
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(NewRole::class, 'role_id');
    }

    /**
     * Get the school context for this assignment
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who assigned this role
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who revoked this role
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Get assignment display name
     */
    public function getDisplayNameAttribute(): string
    {
        $userName = $this->user->name ?? 'Unknown User';
        $roleName = $this->role->display_name ?? 'Unknown Role';
        $schoolName = $this->school->name ?? 'System Wide';
        
        return "{$userName} - {$roleName} ({$schoolName})";
    }

    /**
     * Get assignment status display
     */
    public function getStatusDisplayAttribute(): string
    {
        if ($this->revoked_at) {
            return 'Revoked';
        }
        
        if (!$this->is_active) {
            return 'Inactive';
        }
        
        if ($this->isExpired()) {
            return 'Expired';
        }
        
        if ($this->isTemporary()) {
            return 'Temporary';
        }
        
        return 'Active';
    }

    /**
     * Check if assignment is currently active
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active 
            && !$this->revoked_at 
            && !$this->isExpired();
    }

    /**
     * Check if assignment is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if assignment is temporary (has expiration)
     */
    public function isTemporary(): bool
    {
        return !is_null($this->expires_at);
    }

    /**
     * Check if assignment is permanent
     */
    public function isPermanent(): bool
    {
        return is_null($this->expires_at);
    }

    /**
     * Check if assignment is revoked
     */
    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }

    /**
     * Check if assignment is primary role for user
     */
    public function isPrimaryRole(): bool
    {
        return $this->is_primary;
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        
        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Get assignment duration in days
     */
    public function getAssignmentDurationInDays(): int
    {
        $endDate = $this->revoked_at ?? $this->expires_at ?? now();
        return $this->assigned_at->diffInDays($endDate);
    }

    /**
     * Check if assignment is expiring soon
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        
        $daysUntilExpiration = $this->getDaysUntilExpiration();
        return $daysUntilExpiration !== null && $daysUntilExpiration <= $days && $daysUntilExpiration > 0;
    }

    /**
     * Get context information
     */
    public function getContextInfo(): array
    {
        return $this->context_data ?? [];
    }

    /**
     * Set context information
     */
    public function setContextInfo(array $data): void
    {
        $this->context_data = array_merge($this->getContextInfo(), $data);
        $this->save();
    }

    /**
     * Scope: Active assignments only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->whereNull('revoked_at')
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope: Assignments by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Assignments by role
     */
    public function scopeByRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope: Assignments by school
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope: Primary role assignments
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Temporary assignments
     */
    public function scopeTemporary($query)
    {
        return $query->whereNotNull('expires_at');
    }

    /**
     * Scope: Permanent assignments
     */
    public function scopePermanent($query)
    {
        return $query->whereNull('expires_at');
    }

    /**
     * Scope: Expired assignments
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    /**
     * Scope: Expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '>', now())
                    ->where('expires_at', '<=', now()->addDays($days));
    }

    /**
     * Scope: Revoked assignments
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope: Assignments by role name
     */
    public function scopeByRoleName($query, string $roleName)
    {
        return $query->whereHas('role', function($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Scope: Assignments by role level
     */
    public function scopeByRoleLevel($query, int $level)
    {
        return $query->whereHas('role', function($q) use ($level) {
            $q->where('hierarchy_level', $level);
        });
    }

    /**
     * Scope: System-wide assignments (no school context)
     */
    public function scopeSystemWide($query)
    {
        return $query->whereNull('school_id');
    }

    /**
     * Scope: School-specific assignments
     */
    public function scopeSchoolSpecific($query)
    {
        return $query->whereNotNull('school_id');
    }

    /**
     * Get user's active role assignments
     */
    public static function getUserActiveRoles($userId, $schoolId = null): array
    {
        $query = self::where('user_id', $userId)->active();
        
        if ($schoolId) {
            $query->where(function($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->orWhereNull('school_id'); // Include system-wide roles
            });
        }
        
        return $query->with(['role', 'school'])
                    ->orderBy('is_primary', 'desc')
                    ->orderByDesc('assigned_at')
                    ->get()
                    ->map(function($assignment) {
                        return [
                            'assignment_id' => $assignment->id,
                            'role_id' => $assignment->role_id,
                            'role_name' => $assignment->role->name,
                            'role_display_name' => $assignment->role->display_name,
                            'role_level' => $assignment->role->hierarchy_level,
                            'school_id' => $assignment->school_id,
                            'school_name' => $assignment->school->name ?? 'System Wide',
                            'is_primary' => $assignment->is_primary,
                            'is_temporary' => $assignment->isTemporary(),
                            'expires_at' => $assignment->expires_at,
                            'days_until_expiration' => $assignment->getDaysUntilExpiration(),
                            'assigned_at' => $assignment->assigned_at,
                            'context_data' => $assignment->context_data,
                        ];
                    })
                    ->toArray();
    }

    /**
     * Get user's primary role
     */
    public static function getUserPrimaryRole($userId, $schoolId = null)
    {
        $query = self::where('user_id', $userId)
                    ->where('is_primary', true)
                    ->active();
        
        if ($schoolId) {
            $query->where(function($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->orWhereNull('school_id');
            });
        }
        
        return $query->with(['role', 'school'])->first();
    }

    /**
     * Get role assignments for a role
     */
    public static function getRoleAssignments($roleId, $schoolId = null, bool $activeOnly = true): array
    {
        $query = self::where('role_id', $roleId);
        
        if ($activeOnly) {
            $query->active();
        }
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        return $query->with(['user', 'user.profile', 'school', 'assignedBy'])
                    ->orderBy('assigned_at', 'desc')
                    ->get()
                    ->map(function($assignment) {
                        return [
                            'assignment_id' => $assignment->id,
                            'user_id' => $assignment->user_id,
                            'user_name' => $assignment->user->name,
                            'user_email' => $assignment->user->email,
                            'school_name' => $assignment->school->name ?? 'System Wide',
                            'is_primary' => $assignment->is_primary,
                            'assigned_at' => $assignment->assigned_at,
                            'assigned_by_name' => $assignment->assignedBy->name ?? 'System',
                            'expires_at' => $assignment->expires_at,
                            'status' => $assignment->status_display,
                            'context_data' => $assignment->context_data,
                        ];
                    })
                    ->toArray();
    }

    /**
     * Check if user has specific role
     */
    public static function userHasRole($userId, string $roleName, $schoolId = null): bool
    {
        $query = self::where('user_id', $userId)
                    ->active()
                    ->whereHas('role', function($q) use ($roleName) {
                        $q->where('name', $roleName);
                    });
        
        if ($schoolId) {
            $query->where(function($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->orWhereNull('school_id');
            });
        }
        
        return $query->exists();
    }

    /**
     * Check if user has any of the specified roles
     */
    public static function userHasAnyRole($userId, array $roleNames, $schoolId = null): bool
    {
        $query = self::where('user_id', $userId)
                    ->active()
                    ->whereHas('role', function($q) use ($roleNames) {
                        $q->whereIn('name', $roleNames);
                    });
        
        if ($schoolId) {
            $query->where(function($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->orWhereNull('school_id');
            });
        }
        
        return $query->exists();
    }

    /**
     * Get user's highest role level
     */
    public static function getUserHighestRoleLevel($userId, $schoolId = null): ?int
    {
        $query = self::where('user_id', $userId)->active();
        
        if ($schoolId) {
            $query->where(function($q) use ($schoolId) {
                $q->where('school_id', $schoolId)
                  ->orWhereNull('school_id');
            });
        }
        
        return $query->join('new_roles', 'user_role_assignments.role_id', '=', 'new_roles.id')
                    ->min('new_roles.hierarchy_level');
    }

    /**
     * Assign role to user
     */
    public static function assignRole(array $data): self
    {
        // Set default values
        $data['assigned_at'] = $data['assigned_at'] ?? now();
        $data['is_active'] = $data['is_active'] ?? true;
        $data['assigned_by'] = $data['assigned_by'] ?? auth()->id();
        
        // If this is set as primary role, remove primary status from others
        if ($data['is_primary'] ?? false) {
            self::where('user_id', $data['user_id'])
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }
        
        // Check if user already has this role in this context
        $existingAssignment = self::where('user_id', $data['user_id'])
                                 ->where('role_id', $data['role_id'])
                                 ->where('school_id', $data['school_id'] ?? null)
                                 ->active()
                                 ->first();
        
        if ($existingAssignment) {
            // Update existing assignment
            $existingAssignment->update([
                'expires_at' => $data['expires_at'] ?? null,
                'is_primary' => $data['is_primary'] ?? $existingAssignment->is_primary,
                'context_data' => array_merge($existingAssignment->context_data ?? [], $data['context_data'] ?? []),
                'notes' => $data['notes'] ?? $existingAssignment->notes,
            ]);
            
            return $existingAssignment;
        }
        
        return self::create($data);
    }

    /**
     * Revoke role assignment
     */
    public function revoke(string $reason = null, $revokedBy = null): void
    {
        $this->revoked_at = now();
        $this->revoked_by = $revokedBy ?? auth()->id();
        $this->revoke_reason = $reason;
        $this->is_active = false;
        
        $this->save();
        
        // If this was the primary role, promote another role
        if ($this->is_primary) {
            $nextPrimary = self::where('user_id', $this->user_id)
                              ->where('id', '!=', $this->id)
                              ->active()
                              ->orderBy('assigned_at', 'desc')
                              ->first();
            
            if ($nextPrimary) {
                $nextPrimary->setAsPrimary();
            }
        }
    }

    /**
     * Set as primary role
     */
    public function setAsPrimary(): void
    {
        // Remove primary status from other roles for this user
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
        
        // Set this assignment as primary
        $this->is_primary = true;
        $this->save();
    }

    /**
     * Extend assignment expiration
     */
    public function extend(Carbon $newExpirationDate, string $reason = null): void
    {
        $this->expires_at = $newExpirationDate;
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Extended: {$reason}";
        }
        
        $this->save();
    }

    /**
     * Make assignment permanent
     */
    public function makePermanent(string $reason = null): void
    {
        $this->expires_at = null;
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Made permanent: {$reason}";
        }
        
        $this->save();
    }

    /**
     * Deactivate assignment
     */
    public function deactivate(string $reason = null): void
    {
        $this->is_active = false;
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Deactivated: {$reason}";
        }
        
        $this->save();
    }

    /**
     * Reactivate assignment
     */
    public function reactivate(string $reason = null): void
    {
        $this->is_active = true;
        $this->revoked_at = null;
        $this->revoked_by = null;
        $this->revoke_reason = null;
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Reactivated: {$reason}";
        }
        
        $this->save();
    }

    /**
     * Get assignments expiring soon
     */
    public static function getExpiringSoonAssignments(int $days = 30): array
    {
        return self::expiringSoon($days)
                  ->with(['user', 'role', 'school'])
                  ->orderBy('expires_at')
                  ->get()
                  ->map(function($assignment) {
                      return [
                          'assignment_id' => $assignment->id,
                          'user_name' => $assignment->user->name,
                          'user_email' => $assignment->user->email,
                          'role_name' => $assignment->role->display_name,
                          'school_name' => $assignment->school->name ?? 'System Wide',
                          'expires_at' => $assignment->expires_at,
                          'days_until_expiration' => $assignment->getDaysUntilExpiration(),
                          'is_primary' => $assignment->is_primary,
                      ];
                  })
                  ->toArray();
    }

    /**
     * Get assignment statistics
     */
    public static function getAssignmentStatistics($schoolId = null): array
    {
        $query = self::query();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        $assignments = $query->with('role')->get();
        
        return [
            'total_assignments' => $assignments->count(),
            'active_assignments' => $assignments->where('is_active', true)->count(),
            'expired_assignments' => $assignments->filter(function($a) { return $a->isExpired(); })->count(),
            'revoked_assignments' => $assignments->whereNotNull('revoked_at')->count(),
            'temporary_assignments' => $assignments->whereNotNull('expires_at')->count(),
            'permanent_assignments' => $assignments->whereNull('expires_at')->count(),
            'primary_assignments' => $assignments->where('is_primary', true)->count(),
            'assignments_by_role' => $assignments->groupBy('role.name')->map->count()->toArray(),
            'expiring_soon' => $assignments->filter(function($a) { return $a->isExpiringSoon(); })->count(),
        ];
    }
}