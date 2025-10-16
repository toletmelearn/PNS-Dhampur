<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewUser;
use App\Models\NewRole;
use App\Models\School;
use App\Models\UserRoleAssignment;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserRoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
        
        // Apply middleware
        $this->middleware(['auth', 'session.security']);
        $this->middleware('role:' . NewRole::SUPER_ADMIN . ',' . NewRole::ADMIN . ',' . NewRole::PRINCIPAL);
        $this->middleware('permission:user.edit')->only(['assign', 'remove', 'update']);
        $this->middleware('permission:user.view')->only(['show']);
    }

    /**
     * Show user role assignments
     */
    public function show(NewUser $user)
    {
        try {
            $user->load([
                'roles' => function($query) {
                    $query->with(['role', 'school'])
                          ->orderBy('is_primary', 'desc')
                          ->orderBy('assigned_at', 'desc');
                }
            ]);
            
            // Get assignable roles based on current user's role
            $assignableRoles = $this->roleService->getAssignableRoles(auth()->user());
            $schools = School::active()->orderBy('name')->get();
            
            // Get role assignment statistics
            $statistics = [
                'total_assignments' => $user->roles()->count(),
                'active_assignments' => $user->roles()->active()->count(),
                'expired_assignments' => $user->roles()->expired()->count(),
                'temporary_assignments' => $user->roles()->temporary()->count(),
                'permanent_assignments' => $user->roles()->permanent()->count(),
                'revoked_assignments' => $user->roles()->revoked()->count(),
            ];
            
            return view('admin.users.roles', compact('user', 'assignableRoles', 'schools', 'statistics'));
            
        } catch (\Exception $e) {
            Log::error('Error loading user role assignments', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'viewer_id' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'Failed to load user role assignments.');
        }
    }

    /**
     * Assign role to user
     */
    public function assign(Request $request, NewUser $user)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:new_roles,id',
            'school_id' => 'nullable|exists:schools,id',
            'is_primary' => 'boolean',
            'is_temporary' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $role = NewRole::findOrFail($request->role_id);
            
            // Validate role assignment permissions
            if (!$this->roleService->canAssignRole(auth()->user(), $role)) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to assign this role.')
                    ->withInput();
            }
            
            // Check if user already has this role
            $existingAssignment = $user->roles()
                ->where('role_id', $role->id)
                ->where('school_id', $request->school_id)
                ->active()
                ->first();
                
            if ($existingAssignment) {
                return redirect()->back()
                    ->with('error', 'User already has this role assigned for the selected school.')
                    ->withInput();
            }
            
            DB::beginTransaction();
            
            // If this is set as primary role, remove primary flag from other assignments
            if ($request->boolean('is_primary', false)) {
                $user->roles()->update(['is_primary' => false]);
            }
            
            // Create role assignment
            $assignmentData = [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'school_id' => $request->school_id,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'is_primary' => $request->boolean('is_primary', false),
                'is_temporary' => $request->boolean('is_temporary', false),
                'expires_at' => $request->is_temporary ? $request->expires_at : null,
                'notes' => $request->notes,
                'status' => UserRoleAssignment::STATUS_ACTIVE,
            ];
            
            $assignment = UserRoleAssignment::create($assignmentData);
            
            DB::commit();
            
            Log::info('Role assigned to user successfully', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'school_id' => $request->school_id,
                'assignment_id' => $assignment->id,
                'assigned_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.roles.show', $user)
                ->with('success', "Role '{$role->display_name}' assigned successfully.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error assigning role to user', [
                'user_id' => $user->id,
                'role_id' => $request->role_id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'assigner_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to assign role: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update role assignment
     */
    public function update(Request $request, NewUser $user, UserRoleAssignment $assignment)
    {
        // Ensure assignment belongs to the user
        if ($assignment->user_id !== $user->id) {
            return redirect()->route('admin.users.roles.show', $user)
                ->with('error', 'Invalid role assignment.');
        }
        
        $validator = Validator::make($request->all(), [
            'is_primary' => 'boolean',
            'is_temporary' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Validate role assignment permissions
            if (!$this->roleService->canAssignRole(auth()->user(), $assignment->role)) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to modify this role assignment.');
            }
            
            DB::beginTransaction();
            
            // If this is set as primary role, remove primary flag from other assignments
            if ($request->boolean('is_primary', false) && !$assignment->is_primary) {
                $user->roles()->update(['is_primary' => false]);
            }
            
            // Update assignment
            $updateData = [
                'is_primary' => $request->boolean('is_primary', false),
                'is_temporary' => $request->boolean('is_temporary', false),
                'expires_at' => $request->is_temporary ? $request->expires_at : null,
                'notes' => $request->notes,
                'updated_by' => auth()->id(),
            ];
            
            $assignment->update($updateData);
            
            DB::commit();
            
            Log::info('Role assignment updated successfully', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'role_id' => $assignment->role_id,
                'updated_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.roles.show', $user)
                ->with('success', 'Role assignment updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating role assignment', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'updater_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to update role assignment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove role assignment
     */
    public function remove(NewUser $user, UserRoleAssignment $assignment)
    {
        // Ensure assignment belongs to the user
        if ($assignment->user_id !== $user->id) {
            return redirect()->route('admin.users.roles.show', $user)
                ->with('error', 'Invalid role assignment.');
        }
        
        try {
            // Validate role assignment permissions
            if (!$this->roleService->canAssignRole(auth()->user(), $assignment->role)) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to remove this role assignment.');
            }
            
            // Prevent removing the last active role
            $activeAssignments = $user->roles()->active()->count();
            if ($activeAssignments <= 1) {
                return redirect()->back()
                    ->with('error', 'Cannot remove the last active role assignment. User must have at least one role.');
            }
            
            $roleName = $assignment->role->display_name;
            
            // Soft delete the assignment
            $assignment->update([
                'status' => UserRoleAssignment::STATUS_REVOKED,
                'revoked_by' => auth()->id(),
                'revoked_at' => now(),
                'revocation_reason' => 'Removed by administrator'
            ]);
            
            Log::info('Role assignment removed successfully', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'role_id' => $assignment->role_id,
                'removed_by' => auth()->id()
            ]);
            
            return redirect()->route('admin.users.roles.show', $user)
                ->with('success', "Role '{$roleName}' removed successfully.");
                
        } catch (\Exception $e) {
            Log::error('Error removing role assignment', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
                'remover_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to remove role assignment: ' . $e->getMessage());
        }
    }

    /**
     * Make role assignment primary (AJAX)
     */
    public function makePrimary(NewUser $user, UserRoleAssignment $assignment)
    {
        // Ensure assignment belongs to the user
        if ($assignment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role assignment.'
            ], 400);
        }
        
        try {
            // Validate role assignment permissions
            if (!$this->roleService->canAssignRole(auth()->user(), $assignment->role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to modify this role assignment.'
                ], 403);
            }
            
            DB::beginTransaction();
            
            // Remove primary flag from all other assignments
            $user->roles()->update(['is_primary' => false]);
            
            // Set this assignment as primary
            $assignment->update(['is_primary' => true]);
            
            DB::commit();
            
            Log::info('Role assignment set as primary', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'role_id' => $assignment->role_id,
                'set_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Role set as primary successfully.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error setting role as primary', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
                'setter_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to set role as primary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extend role assignment (AJAX)
     */
    public function extend(Request $request, NewUser $user, UserRoleAssignment $assignment)
    {
        // Ensure assignment belongs to the user
        if ($assignment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role assignment.'
            ], 400);
        }
        
        $validator = Validator::make($request->all(), [
            'expires_at' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid expiration date.',
                'errors' => $validator->errors()
            ], 400);
        }
        
        try {
            // Validate role assignment permissions
            if (!$this->roleService->canAssignRole(auth()->user(), $assignment->role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to modify this role assignment.'
                ], 403);
            }
            
            $assignment->update([
                'expires_at' => $request->expires_at,
                'is_temporary' => true,
                'updated_by' => auth()->id()
            ]);
            
            Log::info('Role assignment extended', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'role_id' => $assignment->role_id,
                'new_expiry' => $request->expires_at,
                'extended_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Role assignment extended successfully.',
                'expires_at' => $assignment->expires_at->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error extending role assignment', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
                'extender_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to extend role assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make role assignment permanent (AJAX)
     */
    public function makePermanent(NewUser $user, UserRoleAssignment $assignment)
    {
        // Ensure assignment belongs to the user
        if ($assignment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role assignment.'
            ], 400);
        }
        
        try {
            // Validate role assignment permissions
            if (!$this->roleService->canAssignRole(auth()->user(), $assignment->role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to modify this role assignment.'
                ], 403);
            }
            
            $assignment->update([
                'is_temporary' => false,
                'expires_at' => null,
                'updated_by' => auth()->id()
            ]);
            
            Log::info('Role assignment made permanent', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'role_id' => $assignment->role_id,
                'made_permanent_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Role assignment made permanent successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error making role assignment permanent', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
                'maker_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to make role assignment permanent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivate role assignment (AJAX)
     */
    public function reactivate(NewUser $user, UserRoleAssignment $assignment)
    {
        // Ensure assignment belongs to the user
        if ($assignment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role assignment.'
            ], 400);
        }
        
        try {
            // Validate role assignment permissions
            if (!$this->roleService->canAssignRole(auth()->user(), $assignment->role)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to modify this role assignment.'
                ], 403);
            }
            
            $assignment->update([
                'status' => UserRoleAssignment::STATUS_ACTIVE,
                'reactivated_by' => auth()->id(),
                'reactivated_at' => now(),
                'revoked_by' => null,
                'revoked_at' => null,
                'revocation_reason' => null
            ]);
            
            Log::info('Role assignment reactivated', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'role_id' => $assignment->role_id,
                'reactivated_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Role assignment reactivated successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error reactivating role assignment', [
                'user_id' => $user->id,
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
                'reactivator_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reactivate role assignment: ' . $e->getMessage()
            ], 500);
        }
    }
}