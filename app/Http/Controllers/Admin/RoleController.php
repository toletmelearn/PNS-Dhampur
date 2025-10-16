<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewRole;
use App\Models\Permission;
use App\Services\RoleService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    protected $roleService;
    protected $permissionService;

    public function __construct(RoleService $roleService, PermissionService $permissionService)
    {
        $this->roleService = $roleService;
        $this->permissionService = $permissionService;
        
        // Apply middleware
        $this->middleware(['auth', 'session.security']);
        $this->middleware('role:' . NewRole::SUPER_ADMIN . ',' . NewRole::ADMIN);
        $this->middleware('permission:role.view')->only(['index', 'show']);
        $this->middleware('permission:role.create')->only(['create', 'store']);
        $this->middleware('permission:role.edit')->only(['edit', 'update']);
        $this->middleware('permission:role.delete')->only(['destroy']);
    }

    /**
     * Display a listing of roles
     */
    public function index(Request $request)
    {
        try {
            $query = NewRole::with(['permissions', 'users']);
            
            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            // Filter by status
            if ($request->filled('status')) {
                if ($request->get('status') === 'active') {
                    $query->active();
                } elseif ($request->get('status') === 'inactive') {
                    $query->where('is_active', false);
                }
            }
            
            // Filter by hierarchy level
            if ($request->filled('hierarchy_level')) {
                $query->where('hierarchy_level', $request->get('hierarchy_level'));
            }
            
            // Sort
            $sortBy = $request->get('sort_by', 'hierarchy_level');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
            
            $roles = $query->paginate(15);
            
            // Get role statistics
            $statistics = $this->roleService->getRoleStatistics();
            
            // Get role hierarchy
            $hierarchy = $this->roleService->getRoleHierarchy();
            
            return view('admin.roles.index', compact('roles', 'statistics', 'hierarchy'));
            
        } catch (\Exception $e) {
            Log::error('Error loading roles index', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()->with('error', 'Failed to load roles. Please try again.');
        }
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        try {
            $permissions = $this->permissionService->getPermissionsByModule();
            $hierarchyLevels = NewRole::getHierarchyLevels();
            
            return view('admin.roles.create', compact('permissions', 'hierarchyLevels'));
            
        } catch (\Exception $e) {
            Log::error('Error loading role creation form', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.roles.index')->with('error', 'Failed to load role creation form.');
        }
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:new_roles,name|regex:/^[a-z_]+$/',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'hierarchy_level' => 'required|integer|min:1|max:10',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
            'can_create_users' => 'boolean',
            'can_assign_roles' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $roleData = $request->only([
                'name', 'display_name', 'description', 'hierarchy_level',
                'can_create_users', 'can_assign_roles', 'is_active', 'sort_order'
            ]);
            
            $roleData['permission_ids'] = $request->get('permission_ids', []);
            $roleData['is_system_role'] = false; // Custom roles are not system roles
            
            $role = $this->roleService->createRole($roleData);
            
            Log::info('Role created successfully', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'created_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error creating role', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to create role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified role
     */
    public function show(NewRole $role)
    {
        try {
            $role->load(['permissions', 'users.profile']);
            
            // Get role statistics
            $statistics = [
                'total_users' => $role->users()->count(),
                'active_users' => $role->users()->active()->count(),
                'total_permissions' => $role->permissions()->count(),
                'active_permissions' => $role->permissions()->active()->count(),
            ];
            
            // Get recent role assignments
            $recentAssignments = $role->roleAssignments()
                ->with(['user', 'assignedBy'])
                ->latest()
                ->limit(10)
                ->get();
            
            return view('admin.roles.show', compact('role', 'statistics', 'recentAssignments'));
            
        } catch (\Exception $e) {
            Log::error('Error loading role details', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.roles.index')
                ->with('error', 'Failed to load role details.');
        }
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(NewRole $role)
    {
        try {
            // Prevent editing system roles unless super admin
            if ($role->is_system_role && !auth()->user()->hasRole(NewRole::SUPER_ADMIN)) {
                return redirect()->route('admin.roles.index')
                    ->with('error', 'System roles can only be edited by Super Administrators.');
            }
            
            $role->load('permissions');
            $permissions = $this->permissionService->getPermissionsByModule();
            $hierarchyLevels = NewRole::getHierarchyLevels();
            
            return view('admin.roles.edit', compact('role', 'permissions', 'hierarchyLevels'));
            
        } catch (\Exception $e) {
            Log::error('Error loading role edit form', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.roles.index')
                ->with('error', 'Failed to load role edit form.');
        }
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, NewRole $role)
    {
        // Prevent editing system roles unless super admin
        if ($role->is_system_role && !auth()->user()->hasRole(NewRole::SUPER_ADMIN)) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'System roles can only be edited by Super Administrators.');
        }
        
        $validator = Validator::make($request->all(), [
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
            'can_create_users' => 'boolean',
            'can_assign_roles' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $roleData = $request->only([
                'display_name', 'description', 'can_create_users', 
                'can_assign_roles', 'is_active', 'sort_order'
            ]);
            
            $roleData['permission_ids'] = $request->get('permission_ids', []);
            
            $role = $this->roleService->updateRole($role, $roleData);
            
            Log::info('Role updated successfully', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'updated_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.roles.show', $role)
                ->with('success', 'Role updated successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error updating role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to update role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy(NewRole $role)
    {
        try {
            // Prevent deleting system roles
            if ($role->is_system_role) {
                return redirect()->route('admin.roles.index')
                    ->with('error', 'System roles cannot be deleted.');
            }
            
            // Check if role has active users
            $activeUsers = $role->users()->active()->count();
            if ($activeUsers > 0) {
                return redirect()->route('admin.roles.index')
                    ->with('error', "Cannot delete role. It has {$activeUsers} active users assigned.");
            }
            
            $roleName = $role->name;
            $role->delete();
            
            Log::info('Role deleted successfully', [
                'role_name' => $roleName,
                'deleted_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.roles.index')
                ->with('success', 'Role deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.roles.index')
                ->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Get role permissions matrix (AJAX)
     */
    public function permissionsMatrix()
    {
        try {
            $matrix = $this->permissionService->getRolePermissionsMatrix();
            
            return response()->json([
                'success' => true,
                'data' => $matrix
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading permissions matrix', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load permissions matrix.'
            ], 500);
        }
    }

    /**
     * Sync role with default permissions (AJAX)
     */
    public function syncDefaultPermissions(NewRole $role)
    {
        try {
            $this->roleService->syncRolePermissions($role);
            
            Log::info('Role permissions synced with defaults', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'synced_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Role permissions synced with defaults successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error syncing role permissions', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync role permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle role status (AJAX)
     */
    public function toggleStatus(NewRole $role)
    {
        try {
            // Prevent deactivating system roles
            if ($role->is_system_role && $role->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be deactivated.'
                ], 400);
            }
            
            $role->update(['is_active' => !$role->is_active]);
            
            Log::info('Role status toggled', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'new_status' => $role->is_active ? 'active' : 'inactive',
                'toggled_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Role status updated successfully.',
                'is_active' => $role->is_active
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error toggling role status', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role status: ' . $e->getMessage()
            ], 500);
        }
    }
}