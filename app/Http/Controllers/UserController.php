<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Rules\PasswordComplexity;
use App\Rules\PasswordHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use League\Csv\Reader;
use League\Csv\Statement;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role if provided
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(15);

        return response()->json($users);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // Return view for creating user form if needed
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'confirmed',
                new PasswordComplexity(null, $request->role)
            ],
            'role' => 'required|in:admin,teacher,student'
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => 'temp', // Temporary password
        ]);

        // Use the new updatePassword method which handles history and expiration
        $user->updatePassword($data['password']);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'confirmed',
                new PasswordComplexity($user, $user->role),
                new PasswordHistory($user)
            ],
            'role' => 'sometimes|required|in:admin,teacher,student'
        ]);

        if (isset($data['password']) && $data['password']) {
            // Use the new updatePassword method which handles history and expiration
            $user->updatePassword($data['password']);
            unset($data['password']); // Remove from data array since it's handled separately
        }

        // Update other fields
        $user->update(collect($data)->except('password')->toArray());

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deletion of the current authenticated user
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Cannot delete your own account'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Bulk import users from CSV file
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkImportUsers(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'update_existing' => 'boolean',
            'send_welcome_email' => 'boolean'
        ]);

        try {
            $file = $request->file('csv_file');
            $updateExisting = $request->boolean('update_existing', false);
            $sendWelcomeEmail = $request->boolean('send_welcome_email', true);
            
            // Read CSV file
            $csv = Reader::createFromPath($file->getPathname(), 'r');
            $csv->setHeaderOffset(0);
            
            $records = Statement::create()->process($csv);
            
            $imported = 0;
            $updated = 0;
            $errors = [];
            $duplicates = [];
            
            DB::beginTransaction();
            
            foreach ($records as $offset => $record) {
                try {
                    // Validate required fields
                    if (empty($record['name']) || empty($record['email'])) {
                        $errors[] = "Row " . ($offset + 2) . ": Name and email are required";
                        continue;
                    }
                    
                    // Check if user exists
                    $existingUser = User::where('email', $record['email'])->first();
                    
                    if ($existingUser && !$updateExisting) {
                        $duplicates[] = $record['email'];
                        continue;
                    }
                    
                    // Prepare user data
                    $userData = [
                        'name' => $record['name'],
                        'email' => $record['email'],
                        'role' => $record['role'] ?? 'student',
                        'phone' => $record['phone'] ?? null,
                        'address' => $record['address'] ?? null,
                        'status' => $record['status'] ?? 'active'
                    ];
                    
                    // Generate password if not provided
                    if (empty($record['password'])) {
                        $userData['password'] = Hash::make(Str::random(12));
                        $userData['password_reset_required'] = true;
                    } else {
                        $userData['password'] = Hash::make($record['password']);
                        $userData['password_reset_required'] = false;
                    }
                    
                    if ($existingUser && $updateExisting) {
                        // Update existing user
                        $existingUser->update($userData);
                        $updated++;
                        
                        // Send notification email if requested
                        if ($sendWelcomeEmail) {
                            // Mail::to($existingUser->email)->send(new UserUpdatedMail($existingUser));
                        }
                    } else {
                        // Create new user
                        $user = User::create($userData);
                        $imported++;
                        
                        // Send welcome email if requested
                        if ($sendWelcomeEmail) {
                            // Mail::to($user->email)->send(new WelcomeMail($user));
                        }
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($offset + 2) . ": " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Bulk import completed',
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors,
                'duplicates' => $duplicates,
                'total_processed' => $imported + $updated + count($errors) + count($duplicates)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk import failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Bulk import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset passwords for multiple users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resetPasswords(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'password_type' => 'required|in:random,custom,default',
            'custom_password' => 'required_if:password_type,custom|min:8',
            'send_email' => 'boolean',
            'force_reset' => 'boolean'
        ]);

        try {
            $userIds = $request->user_ids;
            $passwordType = $request->password_type;
            $customPassword = $request->custom_password;
            $sendEmail = $request->boolean('send_email', true);
            $forceReset = $request->boolean('force_reset', true);
            
            $resetUsers = [];
            $errors = [];
            
            DB::beginTransaction();
            
            foreach ($userIds as $userId) {
                try {
                    $user = User::findOrFail($userId);
                    
                    // Prevent resetting admin password unless current user is admin
                    if ($user->role === 'admin' && auth()->user()->role !== 'admin') {
                        $errors[] = "Cannot reset password for admin user: {$user->email}";
                        continue;
                    }
                    
                    // Generate new password based on type
                    switch ($passwordType) {
                        case 'random':
                            $newPassword = Str::random(12);
                            break;
                        case 'custom':
                            $newPassword = $customPassword;
                            break;
                        case 'default':
                            $newPassword = 'password123';
                            break;
                    }
                    
                    // Update user password
                    $user->update([
                        'password' => Hash::make($newPassword),
                        'password_reset_required' => $forceReset,
                        'password_changed_at' => now()
                    ]);
                    
                    $resetUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'new_password' => $passwordType === 'random' ? $newPassword : '***'
                    ];
                    
                    // Send email notification if requested
                    if ($sendEmail) {
                        // Mail::to($user->email)->send(new PasswordResetMail($user, $newPassword));
                    }
                    
                    // Log the password reset
                    Log::info("Password reset for user: {$user->email} by " . auth()->user()->email);
                    
                } catch (\Exception $e) {
                    $errors[] = "Failed to reset password for user ID {$userId}: " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Password reset completed',
                'reset_users' => $resetUsers,
                'errors' => $errors,
                'total_processed' => count($resetUsers) + count($errors)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk password reset failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Bulk password reset failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permission templates for different roles
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function permissionTemplates(Request $request)
    {
        try {
            $templates = [
                'admin' => [
                    'name' => 'Administrator',
                    'description' => 'Full system access with all permissions',
                    'permissions' => [
                        'user_management' => [
                            'create_users',
                            'edit_users',
                            'delete_users',
                            'view_users',
                            'bulk_import_users',
                            'reset_passwords',
                            'manage_roles'
                        ],
                        'academic_management' => [
                            'manage_classes',
                            'manage_subjects',
                            'manage_syllabus',
                            'manage_exams',
                            'view_results',
                            'generate_reports'
                        ],
                        'financial_management' => [
                            'manage_fees',
                            'view_payments',
                            'generate_invoices',
                            'manage_budget',
                            'view_financial_reports'
                        ],
                        'system_management' => [
                            'system_settings',
                            'backup_restore',
                            'audit_logs',
                            'security_settings'
                        ]
                    ]
                ],
                'principal' => [
                    'name' => 'Principal',
                    'description' => 'Senior management access with oversight permissions',
                    'permissions' => [
                        'user_management' => [
                            'view_users',
                            'edit_users',
                            'manage_teachers'
                        ],
                        'academic_management' => [
                            'manage_classes',
                            'manage_subjects',
                            'approve_syllabus',
                            'view_exams',
                            'view_results',
                            'generate_reports'
                        ],
                        'financial_management' => [
                            'view_fees',
                            'view_payments',
                            'view_financial_reports',
                            'approve_budget'
                        ],
                        'oversight' => [
                            'view_audit_logs',
                            'approve_changes',
                            'system_monitoring'
                        ]
                    ]
                ],
                'teacher' => [
                    'name' => 'Teacher',
                    'description' => 'Teaching staff with classroom management permissions',
                    'permissions' => [
                        'classroom_management' => [
                            'view_assigned_classes',
                            'manage_attendance',
                            'grade_assignments',
                            'create_exams',
                            'view_student_profiles'
                        ],
                        'academic_activities' => [
                            'create_lesson_plans',
                            'upload_materials',
                            'schedule_activities',
                            'communicate_parents'
                        ],
                        'assessment' => [
                            'create_assessments',
                            'grade_students',
                            'generate_progress_reports',
                            'track_performance'
                        ]
                    ]
                ],
                'accountant' => [
                    'name' => 'Accountant',
                    'description' => 'Financial management with accounting permissions',
                    'permissions' => [
                        'financial_management' => [
                            'manage_fees',
                            'process_payments',
                            'generate_invoices',
                            'manage_expenses',
                            'view_financial_reports'
                        ],
                        'reporting' => [
                            'generate_financial_reports',
                            'export_data',
                            'audit_transactions'
                        ]
                    ]
                ],
                'librarian' => [
                    'name' => 'Librarian',
                    'description' => 'Library management permissions',
                    'permissions' => [
                        'library_management' => [
                            'manage_books',
                            'track_borrowing',
                            'manage_inventory',
                            'generate_library_reports'
                        ]
                    ]
                ],
                'student' => [
                    'name' => 'Student',
                    'description' => 'Student access with limited permissions',
                    'permissions' => [
                        'academic_access' => [
                            'view_profile',
                            'view_grades',
                            'view_attendance',
                            'access_materials',
                            'submit_assignments'
                        ],
                        'communication' => [
                            'message_teachers',
                            'view_announcements'
                        ]
                    ]
                ],
                'parent' => [
                    'name' => 'Parent/Guardian',
                    'description' => 'Parent access to child information',
                    'permissions' => [
                        'child_monitoring' => [
                            'view_child_profile',
                            'view_child_grades',
                            'view_child_attendance',
                            'communicate_teachers',
                            'view_fee_status'
                        ]
                    ]
                ]
            ];

            // Filter templates based on request
            if ($request->filled('role')) {
                $role = $request->role;
                if (isset($templates[$role])) {
                    return response()->json([
                        'template' => $templates[$role],
                        'role' => $role
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Template not found for role: ' . $role
                    ], 404);
                }
            }

            // Return all templates
            return response()->json([
                'templates' => $templates,
                'available_roles' => array_keys($templates)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get permission templates: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to get permission templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply permission template to user(s)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function applyPermissionTemplate(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'template_role' => 'required|string|in:admin,principal,teacher,accountant,librarian,student,parent'
        ]);

        try {
            $userIds = $request->user_ids;
            $templateRole = $request->template_role;
            
            $updatedUsers = [];
            $errors = [];
            
            DB::beginTransaction();
            
            foreach ($userIds as $userId) {
                try {
                    $user = User::findOrFail($userId);
                    
                    // Update user role
                    $user->update(['role' => $templateRole]);
                    
                    $updatedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'old_role' => $user->getOriginal('role'),
                        'new_role' => $templateRole
                    ];
                    
                    // Log the role change
                    Log::info("Role changed for user: {$user->email} from {$user->getOriginal('role')} to {$templateRole} by " . auth()->user()->email);
                    
                } catch (\Exception $e) {
                    $errors[] = "Failed to update user ID {$userId}: " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Permission template applied successfully',
                'updated_users' => $updatedUsers,
                'errors' => $errors,
                'template_applied' => $templateRole
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to apply permission template: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to apply permission template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show bulk import form
     */
    public function showBulkImport()
    {
        return view('users.bulk-import');
    }

    /**
     * Show bulk password reset form
     */
    public function showBulkPasswordReset()
    {
        return view('users.bulk-password-reset');
    }

    /**
     * Get CSV template for bulk import
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadImportTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user_import_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'name',
                'email', 
                'password',
                'role',
                'phone',
                'address',
                'date_of_birth',
                'gender'
            ]);
            
            // Sample data
            fputcsv($file, [
                'John Doe',
                'john.doe@school.edu',
                'password123',
                'teacher',
                '+1234567890',
                '123 Main St, City',
                '1990-01-15',
                'male'
            ]);
            
            fputcsv($file, [
                'Jane Smith',
                'jane.smith@school.edu',
                'password123',
                'student',
                '+1234567891',
                '456 Oak Ave, City',
                '2005-03-20',
                'female'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
