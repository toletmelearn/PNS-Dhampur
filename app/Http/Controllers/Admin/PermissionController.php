<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\NewRole;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
        
        // Apply middleware
        $this->middleware(['auth', 'session.security']);
        $this->middleware('role:' . NewRole::SUPER_ADMIN . ',' . NewRole::ADMIN);
        $this->middleware('permission:permission.view')->only(['index', 'show']);
        $this->middleware('permission:permission.create')->only(['create', 'store']);
        $this->middleware('permission:permission.edit')->only(['edit', 'update']);
        $this->middleware('permission:permission.delete')->only(['destroy']);
    }

    /**
     * Display a listing of permissions
     */
    public function index(Request $request)
    {
        try {
            $query = Permission::query();
            
            // Search functionality
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('module', 'like', "%{$search}%");
                });
            }
            
            // Filter by module
            if ($request->filled('module')) {
                $query->where('module', $request->get('module'));
            }
            
            // Filter by scope
            if ($request->filled('scope')) {
                $query->where('scope', $request->get('scope'));
            }
            
            // Filter by status
            if ($request->filled('status')) {
                if ($request->get('status') === 'active') {
                    $query->active();
                } elseif ($request->get('status') === 'inactive') {
                    $query->where('is_active', false);
                }
            }
            
            // Filter by type
            if ($request->filled('type')) {
                if ($request->get('type') === 'system') {
                    $query->where('is_system_permission', true);
                } elseif ($request->get('type') === 'custom') {
                    $query->where('is_system_permission', false);
                }
            }
            
            // Sort
            $sortBy = $request->get('sort_by', 'module');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
            $query->orderBy('sort_order', 'asc');
            
            $permissions = $query->paginate(20);
            
            // Get permission statistics
            $statistics = $this->permissionService->getPermissionStatistics();
            
            // Get permissions grouped by module
            $permissionsByModule = $this->permissionService->getPermissionsByModule();
            
            // Get available modules and scopes for filters
            $modules = Permission::distinct()->pluck('module')->sort();
            $scopes = Permission::distinct()->pluck('scope')->sort();
            
            return view('admin.permissions.index', compact(
                'permissions', 'statistics', 'permissionsByModule', 'modules', 'scopes'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error loading permissions index', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()->with('error', 'Failed to load permissions. Please try again.');
        }
    }

    /**
     * Show the form for creating a new permission
     */
    public function create()
    {
        try {
            $modules = Permission::getAvailableModules();
            $actions = Permission::getAvailableActions();
            $scopes = Permission::getAvailableScopes();
            
            return view('admin.permissions.create', compact('modules', 'actions', 'scopes'));
            
        } catch (\Exception $e) {
            Log::error('Error loading permission creation form', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Failed to load permission creation form.');
        }
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:permissions,name|regex:/^[a-z_]+\.[a-z_]+(\.[a-z_]+)?$/',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'module' => 'required|string|max:50',
            'action' => 'required|string|max:50',
            'resource' => 'nullable|string|max:50',
            'scope' => 'required|string|in:' . implode(',', Permission::getAvailableScopes()),
            'conditions' => 'nullable|json',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Validate permission name format
            if (!$this->permissionService->validatePermissionName($request->name)) {
                return redirect()->back()
                    ->with('error', 'Invalid permission name format. Use: module.action or module.action.resource')
                    ->withInput();
            }
            
            $permissionData = $request->only([
                'name', 'display_name', 'description', 'module', 'action', 
                'resource', 'scope', 'conditions', 'is_active', 'sort_order'
            ]);
            
            $permissionData['is_system_permission'] = false; // Custom permissions are not system permissions
            
            $permission = $this->permissionService->createPermission($permissionData);
            
            Log::info('Permission created successfully', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'created_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('success', 'Permission created successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error creating permission', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to create permission: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified permission
     */
    public function show(Permission $permission)
    {
        try {
            $permission->load('roles');
            
            // Get permission statistics
            $statistics = [
                'total_roles' => $permission->roles()->count(),
                'active_roles' => $permission->roles()->active()->count(),
                'total_users' => $permission->roles()->withCount('users')->get()->sum('users_count'),
            ];
            
            // Get roles that have this permission
            $rolesWithPermission = $permission->roles()
                ->with('users')
                ->orderBy('hierarchy_level')
                ->get();
            
            return view('admin.permissions.show', compact('permission', 'statistics', 'rolesWithPermission'));
            
        } catch (\Exception $e) {
            Log::error('Error loading permission details', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Failed to load permission details.');
        }
    }

    /**
     * Show the form for editing the specified permission
     */
    public function edit(Permission $permission)
    {
        try {
            // Prevent editing system permissions unless super admin
            if ($permission->is_system_permission && !auth()->user()->hasRole(NewRole::SUPER_ADMIN)) {
                return redirect()->route('admin.permissions.index')
                    ->with('error', 'System permissions can only be edited by Super Administrators.');
            }
            
            $modules = Permission::getAvailableModules();
            $actions = Permission::getAvailableActions();
            $scopes = Permission::getAvailableScopes();
            
            return view('admin.permissions.edit', compact('permission', 'modules', 'actions', 'scopes'));
            
        } catch (\Exception $e) {
            Log::error('Error loading permission edit form', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Failed to load permission edit form.');
        }
    }

    /**
     * Update the specified permission
     */
    public function update(Request $request, Permission $permission)
    {
        // Prevent editing system permissions unless super admin
        if ($permission->is_system_permission && !auth()->user()->hasRole(NewRole::SUPER_ADMIN)) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'System permissions can only be edited by Super Administrators.');
        }
        
        $validator = Validator::make($request->all(), [
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'module' => 'required|string|max:50',
            'action' => 'required|string|max:50',
            'resource' => 'nullable|string|max:50',
            'scope' => 'required|string|in:' . implode(',', Permission::getAvailableScopes()),
            'conditions' => 'nullable|json',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $permissionData = $request->only([
                'display_name', 'description', 'module', 'action', 
                'resource', 'scope', 'conditions', 'is_active', 'sort_order'
            ]);
            
            $permission = $this->permissionService->updatePermission($permission, $permissionData);
            
            Log::info('Permission updated successfully', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'updated_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.show', $permission)
                ->with('success', 'Permission updated successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error updating permission', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to update permission: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified permission
     */
    public function destroy(Permission $permission)
    {
        try {
            // Prevent deleting system permissions
            if ($permission->is_system_permission) {
                return redirect()->route('admin.permissions.index')
                    ->with('error', 'System permissions cannot be deleted.');
            }
            
            // Check if permission is assigned to any roles
            $assignedRoles = $permission->roles()->count();
            if ($assignedRoles > 0) {
                return redirect()->route('admin.permissions.index')
                    ->with('error', "Cannot delete permission. It is assigned to {$assignedRoles} roles.");
            }
            
            $permissionName = $permission->name;
            $permission->delete();
            
            Log::info('Permission deleted successfully', [
                'permission_name' => $permissionName,
                'deleted_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('success', 'Permission deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting permission', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }

    /**
     * Create default system permissions
     */
    public function createDefaults()
    {
        try {
            $created = $this->permissionService->createDefaultPermissions();
            
            Log::info('Default permissions created', [
                'created_count' => $created,
                'created_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('success', "Created {$created} default system permissions.");
                
        } catch (\Exception $e) {
            Log::error('Error creating default permissions', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Failed to create default permissions: ' . $e->getMessage());
        }
    }

    /**
     * Export permissions
     */
    public function export()
    {
        try {
            $permissions = $this->permissionService->exportPermissions();
            
            $filename = 'permissions_export_' . date('Y-m-d_H-i-s') . '.json';
            
            Log::info('Permissions exported', [
                'permissions_count' => count($permissions),
                'exported_by' => auth()->id()
            ]);
            
            return response()->json($permissions)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
        } catch (\Exception $e) {
            Log::error('Error exporting permissions', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Failed to export permissions: ' . $e->getMessage());
        }
    }

    /**
     * Import permissions
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permissions_file' => 'required|file|mimes:json|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        try {
            $file = $request->file('permissions_file');
            $content = file_get_contents($file->getRealPath());
            $permissions = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()
                    ->with('error', 'Invalid JSON file format.');
            }
            
            $imported = $this->permissionService->importPermissions($permissions);
            
            Log::info('Permissions imported', [
                'imported_count' => $imported,
                'total_permissions' => count($permissions),
                'imported_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.permissions.index')
                ->with('success', "Imported {$imported} permissions successfully.");
                
        } catch (\Exception $e) {
            Log::error('Error importing permissions', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to import permissions: ' . $e->getMessage());
        }
    }

    /**
     * Toggle permission status (AJAX)
     */
    public function toggleStatus(Permission $permission)
    {
        try {
            // Prevent deactivating system permissions
            if ($permission->is_system_permission && $permission->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'System permissions cannot be deactivated.'
                ], 400);
            }
            
            $permission->update(['is_active' => !$permission->is_active]);
            
            Log::info('Permission status toggled', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'new_status' => $permission->is_active ? 'active' : 'inactive',
                'toggled_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Permission status updated successfully.',
                'is_active' => $permission->is_active
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error toggling permission status', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate permission name (AJAX)
     */
    public function generateName(Request $request)
    {
        try {
            $module = $request->get('module');
            $action = $request->get('action');
            $resource = $request->get('resource');
            
            if (!$module || !$action) {
                return response()->json([
                    'success' => false,
                    'message' => 'Module and action are required.'
                ], 400);
            }
            
            $name = $this->permissionService->generatePermissionName($module, $action, $resource);
            
            return response()->json([
                'success' => true,
                'name' => $name
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate permission name: ' . $e->getMessage()
            ], 500);
        }
    }
}