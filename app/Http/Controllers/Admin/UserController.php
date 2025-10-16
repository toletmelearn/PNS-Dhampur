<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewUser;
use App\Models\NewRole;
use App\Models\School;
use App\Models\UserProfile;
use App\Models\UserRoleAssignment;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
        
        // Apply middleware
        $this->middleware(['auth', 'session.security']);
        $this->middleware('role:' . NewRole::SUPER_ADMIN . ',' . NewRole::ADMIN . ',' . NewRole::PRINCIPAL);
        $this->middleware('permission:user.view')->only(['index', 'show']);
        $this->middleware('permission:user.create')->only(['create', 'store']);
        $this->middleware('permission:user.edit')->only(['edit', 'update']);
        $this->middleware('permission:user.delete')->only(['destroy']);
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        try {
            $query = NewUser::with(['profile', 'roles', 'school']);
            
            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhereHas('profile', function($pq) use ($search) {
                          $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                      });
                });
            }
            
            // Filter by role
            if ($request->filled('role')) {
                $query->byRole($request->get('role'));
            }
            
            // Filter by school
            if ($request->filled('school_id')) {
                $query->bySchool($request->get('school_id'));
            }
            
            // Filter by status
            if ($request->filled('status')) {
                switch ($request->get('status')) {
                    case 'active':
                        $query->active();
                        break;
                    case 'locked':
                        $query->locked();
                        break;
                    case 'suspended':
                        $query->where('status', NewUser::STATUS_SUSPENDED);
                        break;
                    case 'password_expired':
                        $query->passwordExpired();
                        break;
                    case 'must_change_password':
                        $query->mustChangePassword();
                        break;
                }
            }
            
            // Filter by verification status
            if ($request->filled('verification')) {
                switch ($request->get('verification')) {
                    case 'email_verified':
                        $query->whereNotNull('email_verified_at');
                        break;
                    case 'email_unverified':
                        $query->whereNull('email_verified_at');
                        break;
                    case 'phone_verified':
                        $query->whereNotNull('phone_verified_at');
                        break;
                    case 'phone_unverified':
                        $query->whereNull('phone_verified_at');
                        break;
                }
            }
            
            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if ($sortBy === 'name') {
                $query->join('user_profiles', 'new_users.id', '=', 'user_profiles.user_id')
                      ->orderBy('user_profiles.first_name', $sortOrder)
                      ->orderBy('user_profiles.last_name', $sortOrder)
                      ->select('new_users.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
            
            $users = $query->paginate(20);
            
            // Get filter options
            $roles = NewRole::active()->orderBy('hierarchy_level')->get();
            $schools = School::active()->orderBy('name')->get();
            $statuses = NewUser::getAvailableStatuses();
            
            // Get user statistics
            $statistics = [
                'total_users' => NewUser::count(),
                'active_users' => NewUser::active()->count(),
                'locked_users' => NewUser::locked()->count(),
                'suspended_users' => NewUser::where('status', NewUser::STATUS_SUSPENDED)->count(),
                'password_expired' => NewUser::passwordExpired()->count(),
                'must_change_password' => NewUser::mustChangePassword()->count(),
                'email_unverified' => NewUser::whereNull('email_verified_at')->count(),
                'phone_unverified' => NewUser::whereNull('phone_verified_at')->count(),
            ];
            
            return view('admin.users.index', compact(
                'users', 'roles', 'schools', 'statuses', 'statistics'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error loading users index', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()->with('error', 'Failed to load users. Please try again.');
        }
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        try {
            // Get assignable roles based on current user's role
            $assignableRoles = $this->roleService->getAssignableRoles(auth()->user());
            $schools = School::active()->orderBy('name')->get();
            
            return view('admin.users.create', compact('assignableRoles', 'schools'));
            
        } catch (\Exception $e) {
            Log::error('Error loading user creation form', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.index')
                ->with('error', 'Failed to load user creation form.');
        }
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50|unique:new_users,username|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|email|max:255|unique:new_users,email',
            'phone' => 'nullable|string|max:20|unique:new_users,phone',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:new_roles,id',
            'school_id' => 'nullable|exists:schools,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'must_change_password' => 'boolean',
            'send_welcome_email' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Validate role assignment permissions
            $role = NewRole::findOrFail($request->role_id);
            if (!$this->roleService->canAssignRole(auth()->user(), $role)) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to assign this role.')
                    ->withInput();
            }
            
            DB::beginTransaction();
            
            // Create user
            $userData = [
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'status' => NewUser::STATUS_ACTIVE,
                'must_change_password' => $request->boolean('must_change_password', false),
                'created_by' => auth()->id(),
            ];
            
            $user = NewUser::create($userData);
            
            // Create user profile
            $profileData = [
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'emergency_contact_relationship' => $request->emergency_contact_relationship,
            ];
            
            UserProfile::create($profileData);
            
            // Assign role
            $this->roleService->assignRoleToUser($user, $role, $request->school_id);
            
            DB::commit();
            
            Log::info('User created successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $role->name,
                'created_by' => auth()->id()
            ]);
            
            // Send welcome email if requested
            if ($request->boolean('send_welcome_email', false)) {
                // TODO: Implement welcome email functionality
            }
            
            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating user', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to create user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified user
     */
    public function show(NewUser $user)
    {
        try {
            $user->load([
                'profile',
                'roles.role',
                'school',
                'sessions' => function($query) {
                    $query->orderBy('login_at', 'desc')->limit(10);
                },
                'activities' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(20);
                }
            ]);
            
            // Get user statistics
            $statistics = [
                'total_sessions' => $user->sessions()->count(),
                'active_sessions' => $user->sessions()->active()->count(),
                'total_activities' => $user->activities()->count(),
                'last_login' => $user->sessions()->whereNotNull('login_at')->latest('login_at')->first()?->login_at,
                'failed_login_attempts' => $user->failed_login_attempts,
                'roles_count' => $user->roles()->count(),
                'active_roles_count' => $user->roles()->active()->count(),
            ];
            
            return view('admin.users.show', compact('user', 'statistics'));
            
        } catch (\Exception $e) {
            Log::error('Error loading user details', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'viewer_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.index')
                ->with('error', 'Failed to load user details.');
        }
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(NewUser $user)
    {
        try {
            $user->load(['profile', 'roles.role']);
            
            // Get assignable roles based on current user's role
            $assignableRoles = $this->roleService->getAssignableRoles(auth()->user());
            $schools = School::active()->orderBy('name')->get();
            
            return view('admin.users.edit', compact('user', 'assignableRoles', 'schools'));
            
        } catch (\Exception $e) {
            Log::error('Error loading user edit form', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'editor_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.index')
                ->with('error', 'Failed to load user edit form.');
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, NewUser $user)
    {
        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('new_users', 'username')->ignore($user->id)
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('new_users', 'email')->ignore($user->id)
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('new_users', 'phone')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'status' => 'required|in:' . implode(',', array_keys(NewUser::getAvailableStatuses())),
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'must_change_password' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            // Update user data
            $userData = [
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => $request->status,
                'must_change_password' => $request->boolean('must_change_password', false),
                'two_factor_enabled' => $request->boolean('two_factor_enabled', false),
                'updated_by' => auth()->id(),
            ];
            
            // Update password if provided
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
                $userData['password_changed_at'] = now();
            }
            
            $user->update($userData);
            
            // Update user profile
            $profileData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'emergency_contact_name' => $request->emergency_contact_name,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'emergency_contact_relationship' => $request->emergency_contact_relationship,
            ];
            
            $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);
            
            DB::commit();
            
            Log::info('User updated successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'updated_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'updater_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to update user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(NewUser $user)
    {
        try {
            // Prevent self-deletion
            if ($user->id === auth()->id()) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'You cannot delete your own account.');
            }
            
            // Prevent deleting super admin (unless current user is also super admin)
            if ($user->hasRole(NewRole::SUPER_ADMIN) && !auth()->user()->hasRole(NewRole::SUPER_ADMIN)) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Only Super Administrators can delete other Super Administrators.');
            }
            
            $username = $user->username;
            $user->delete();
            
            Log::info('User deleted successfully', [
                'deleted_user_id' => $user->id,
                'deleted_username' => $username,
                'deleted_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'deleter_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.index')
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Lock user account (AJAX)
     */
    public function lockAccount(NewUser $user)
    {
        try {
            // Prevent locking own account
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot lock your own account.'
                ], 400);
            }
            
            $user->lockAccount(NewUser::LOCK_REASON_ADMIN);
            
            Log::info('User account locked', [
                'locked_user_id' => $user->id,
                'locked_username' => $user->username,
                'locked_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'User account locked successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error locking user account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'locker_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock user account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unlock user account (AJAX)
     */
    public function unlockAccount(NewUser $user)
    {
        try {
            $user->unlockAccount();
            
            Log::info('User account unlocked', [
                'unlocked_user_id' => $user->id,
                'unlocked_username' => $user->username,
                'unlocked_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'User account unlocked successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error unlocking user account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'unlocker_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unlock user account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force password change (AJAX)
     */
    public function forcePasswordChange(NewUser $user)
    {
        try {
            $user->forcePasswordChange();
            
            Log::info('User forced to change password', [
                'user_id' => $user->id,
                'username' => $user->username,
                'forced_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'User will be required to change password on next login.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error forcing password change', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'forcer_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to force password change: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke all user sessions (AJAX)
     */
    public function revokeAllSessions(NewUser $user)
    {
        try {
            $revokedCount = $user->revokeAllSessions();
            
            Log::info('All user sessions revoked', [
                'user_id' => $user->id,
                'username' => $user->username,
                'revoked_sessions' => $revokedCount,
                'revoked_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Revoked {$revokedCount} active sessions."
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error revoking user sessions', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'revoker_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke sessions: ' . $e->getMessage()
            ], 500);
        }
    }
}