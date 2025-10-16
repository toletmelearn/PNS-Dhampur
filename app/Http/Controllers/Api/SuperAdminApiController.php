<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\NewUser;
use App\Models\NewRole;
use App\Models\School;
use App\Models\UserSession;
use App\Models\UserRoleAssignment;
use App\Models\Attendance;
use App\Models\UserProfile;
use App\Services\RoleService;

class SuperAdminApiController extends Controller
{
    /**
     * GET /api/super-admin/users
     * List users with optional filters and pagination
     */
    public function users(Request $request)
    {
        $perPage = (int) ($request->input('per_page') ?: 15);
        $search = trim((string) $request->input('search', ''));
        $role = $request->input('role');
        $schoolId = $request->input('school_id');
        $status = $request->input('status'); // 'active' | 'locked' | etc.
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = NewUser::query()
            ->with(['profile.school', 'roles']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($p) use ($search) {
                      $p->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($role) {
            $query->withRole($role);
        }

        if ($schoolId) {
            $query->inSchool($schoolId);
        }

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'locked') {
            $query->locked();
        }

        // Basic sortable fields
        $sortable = ['created_at', 'updated_at', 'username', 'email'];
        if (!in_array($sortBy, $sortable, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortOrder);

        $paginator = $query->paginate($perPage)->appends($request->query());

        $items = collect($paginator->items())->map(function (NewUser $user) {
            $profile = $user->profile;
            $school = $profile?->school;

            return [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'status' => $user->status,
                'is_active' => (bool) $user->is_active,
                'created_at' => optional($user->created_at)->toIso8601String(),
                'updated_at' => optional($user->updated_at)->toIso8601String(),
                'profile' => $profile ? [
                    'first_name' => $profile->first_name,
                    'last_name' => $profile->last_name,
                    'school' => $school ? [
                        'id' => $school->id,
                        'name' => $school->name,
                        'code' => $school->code,
                        'city' => $school->city,
                    ] : null,
                ] : null,
                'roles' => $user->roles->map(function (NewRole $role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                        'hierarchy_level' => $role->hierarchy_level,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/super-admin/users
     * Create a new admin user (default role: admin)
     */
    public function createUser(Request $request, RoleService $roleService)
    {
        $roleName = $request->input('role_name', NewRole::ADMIN);
        $role = NewRole::where('name', $roleName)->first();
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 422);
        }

        // Permission check for assigning role
        /** @var NewUser $currentUser */
        $currentUser = auth()->user();
        if ($currentUser && !$roleService->canAssignRole($currentUser, $role)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to assign this role.',
            ], 403);
        }

        $userTable = (new NewUser())->getTable();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique($userTable, 'email')],
            'username' => ['nullable', 'string', 'max:255', Rule::unique($userTable, 'username')],
            'phone' => ['nullable', 'string', 'max:25'],
            'password' => ['nullable', 'string', 'min:8'],
            'school_id' => ['nullable', 'integer', Rule::exists((new School())->getTable(), 'id')],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
        ]);

        $password = $validated['password'] ?? bin2hex(random_bytes(8));
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => $password,
            'status' => NewUser::STATUS_ACTIVE,
            'is_active' => true,
            'must_change_password' => true,
        ];

        // Create user and assign role in one go
        $user = NewUser::createWithRole($userData, $role->name, $validated['school_id'] ?? null, [
            'is_primary' => true,
        ]);

        // Create/Update profile if basic details were provided
        if (!empty($validated['first_name']) || !empty($validated['last_name']) || !empty($validated['school_id'])) {
            /** @var UserProfile $profile */
            $profile = $user->profile ?: new UserProfile();
            $profile->fill([
                'user_id' => $user->id,
                'school_id' => $validated['school_id'] ?? $profile->school_id,
                'first_name' => $validated['first_name'] ?? $profile->first_name,
                'last_name' => $validated['last_name'] ?? $profile->last_name,
                'is_active' => true,
            ]);
            $profile->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->roles->map(fn (NewRole $r) => $r->name)->values(),
            ],
        ], 201);
    }

    /**
     * GET /api/super-admin/schools
     * List schools with optional search
     */
    public function schools(Request $request)
    {
        $perPage = (int) ($request->input('per_page') ?: 15);
        $search = trim((string) $request->input('search', ''));

        $query = School::query()->withCount(['userProfiles']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $query->orderBy('name');

        $paginator = $query->paginate($perPage)->appends($request->query());
        $items = collect($paginator->items())->map(function (School $school) {
            return [
                'id' => $school->id,
                'name' => $school->name,
                'code' => $school->code,
                'city' => $school->city,
                'state' => $school->state,
                'is_active' => (bool) $school->is_active,
                'user_profiles_count' => $school->user_profiles_count,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/super-admin/reports/basic
     * Basic counts and summaries for dashboard
     */
    public function reportsBasic()
    {
        $totalUsers = NewUser::count();
        $activeUsers = NewUser::active()->count();
        $lockedUsers = NewUser::locked()->count();
        $totalSchools = School::count();
        $activeSessions = UserSession::getActiveSessionsCount();

        $roleCounts = [
            'super_admin' => UserRoleAssignment::active()->whereHas('role', function ($q) { $q->where('name', NewRole::SUPER_ADMIN); })->count(),
            'admin' => UserRoleAssignment::active()->whereHas('role', function ($q) { $q->where('name', NewRole::ADMIN); })->count(),
            'principal' => UserRoleAssignment::active()->whereHas('role', function ($q) { $q->where('name', NewRole::PRINCIPAL); })->count(),
            'teacher' => UserRoleAssignment::active()->whereHas('role', function ($q) { $q->where('name', NewRole::TEACHER); })->count(),
            'student' => UserRoleAssignment::active()->whereHas('role', function ($q) { $q->where('name', NewRole::STUDENT); })->count(),
            'parent' => UserRoleAssignment::active()->whereHas('role', function ($q) { $q->where('name', NewRole::PARENT); })->count(),
        ];

        $todayAttendance = Attendance::whereDate('date', now()->toDateString())->count();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'locked' => $lockedUsers,
                    'by_role' => $roleCounts,
                ],
                'schools' => [
                    'total' => $totalSchools,
                ],
                'sessions' => [
                    'active' => $activeSessions,
                ],
                'attendance' => [
                    'today_total_records' => $todayAttendance,
                ],
            ],
        ]);
    }

    /**
     * GET /api/super-admin/system/settings
     * Surface minimal system settings for dashboard
     */
    public function systemSettings()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'app_name' => config('app.name'),
                'environment' => config('app.env'),
                'debug' => (bool) config('app.debug'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
            ],
        ]);
    }
}