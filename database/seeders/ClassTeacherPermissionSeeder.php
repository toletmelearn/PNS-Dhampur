<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ClassTeacherPermission;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\AuditTrail;
use Carbon\Carbon;

class ClassTeacherPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get teachers, classes, and subjects
        $teachers = User::where('role', 'teacher')->get();
        $classes = ClassModel::all();
        $currentYear = date('Y');
        $adminUser = User::where('role', 'admin')->first();
        
        if ($teachers->isEmpty() || $classes->isEmpty() || !$adminUser) {
            $this->command->warn('Skipping ClassTeacherPermission seeder: Missing required data (teachers, classes, or admin user)');
            return;
        }
        
        $permissions = [];
        
        foreach ($teachers as $teacher) {
            // Assign each teacher to 1-3 random classes
            $assignedClasses = $classes->random(rand(1, min(3, $classes->count())));
            
            foreach ($assignedClasses as $class) {
                // Decide if this is a class teacher (full permissions) or subject teacher
                $isClassTeacher = rand(1, 4) === 1; // 25% chance of being class teacher
                
                if ($isClassTeacher) {
                    // Class teacher - full permissions for all subjects
                    $permissions[] = [
                        'teacher_id' => $teacher->id,
                        'class_id' => $class->id,
                        'subject_id' => null, // All subjects
                        'can_view_records' => true,
                        'can_edit_records' => true,
                        'can_add_records' => true,
                        'can_delete_records' => false, // Usually restricted
                        'can_export_reports' => true,
                        'can_view_attendance' => true,
                        'can_mark_attendance' => true,
                        'can_approve_corrections' => true,
                        'can_view_audit_trail' => true,
                        'can_bulk_operations' => true,
                        'academic_year' => $currentYear,
                        'valid_from' => Carbon::now()->startOfYear(),
                        'valid_until' => Carbon::now()->endOfYear(),
                        'is_active' => true,
                        'granted_by' => $adminUser->id,
                        'notes' => 'Class Teacher - Full permissions for all subjects',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                } else {
                    // Subject teacher - limited permissions (no specific subjects for now)
                    $permissions[] = [
                        'teacher_id' => $teacher->id,
                        'class_id' => $class->id,
                        'subject_id' => null, // No specific subject for now
                        'can_view_records' => true,
                        'can_edit_records' => rand(0, 1) === 1, // 50% chance
                        'can_add_records' => rand(0, 1) === 1, // 50% chance
                        'can_delete_records' => false,
                        'can_export_reports' => rand(0, 1) === 1, // 50% chance
                        'can_view_attendance' => true,
                        'can_mark_attendance' => rand(0, 1) === 1, // 50% chance
                        'can_approve_corrections' => false, // Usually only class teachers
                        'can_view_audit_trail' => true,
                        'can_bulk_operations' => false,
                        'academic_year' => $currentYear,
                        'valid_from' => Carbon::now()->startOfYear(),
                        'valid_until' => Carbon::now()->endOfYear(),
                        'is_active' => true,
                        'granted_by' => $adminUser->id,
                        'notes' => "Subject Teacher - Limited permissions",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
        
        // Add some expired/revoked permissions for testing
        if ($teachers->count() > 2) {
            $expiredTeacher = $teachers->random();
            $expiredClass = $classes->random();
            
            $permissions[] = [
                'teacher_id' => $expiredTeacher->id,
                'class_id' => $expiredClass->id,
                'subject_id' => null,
                'can_view_records' => true,
                'can_edit_records' => true,
                'can_add_records' => true,
                'can_delete_records' => false,
                'can_export_reports' => true,
                'can_view_attendance' => true,
                'can_mark_attendance' => true,
                'can_approve_corrections' => true,
                'can_view_audit_trail' => true,
                'can_bulk_operations' => true,
                'academic_year' => ($currentYear - 1),
                'valid_from' => Carbon::now()->subYear()->startOfYear(),
                'valid_until' => Carbon::now()->subYear()->endOfYear(),
                'is_active' => false,
                'granted_by' => $adminUser->id,
                'notes' => 'Previous year class teacher permissions',
                'created_at' => Carbon::now()->subYear(),
                'updated_at' => Carbon::now()->subMonths(2),
            ];
        }
        
        // Insert permissions in chunks to avoid memory issues
        $chunks = array_chunk($permissions, 50);
        foreach ($chunks as $chunk) {
            ClassTeacherPermission::insert($chunk);
        }
        
        $this->command->info('Created ' . count($permissions) . ' class teacher permissions');
        
        // Create some sample audit trail entries
        $this->createSampleAuditTrail($teachers, $classes);
    }
    
    /**
     * Create sample audit trail entries
     */
    private function createSampleAuditTrail($teachers, $classes): void
    {
        $events = ['created', 'updated', 'viewed', 'exported'];
        $auditableTypes = ['SRRegister', 'BiometricAttendance', 'ExamPaper'];
        $currentYear = date('Y');
        
        $auditEntries = [];
        
        for ($i = 0; $i < 100; $i++) {
            $teacher = $teachers->random();
            $class = $classes->random();
            $event = $events[array_rand($events)];
            $auditableType = $auditableTypes[array_rand($auditableTypes)];
            
            $requiresApproval = rand(1, 10) === 1; // 10% chance
            $status = 'normal';
            
            if ($requiresApproval) {
                $statusOptions = ['pending_approval', 'approved', 'rejected'];
                $status = $statusOptions[array_rand($statusOptions)];
            }
            
            $auditEntries[] = [
                'user_id' => $teacher->id,
                'user_type' => 'App\Models\User',
                'ip_address' => '192.168.1.' . rand(1, 254),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'url' => '/sr-register',
                'auditable_type' => "App\Models\\{$auditableType}",
                'auditable_id' => rand(1, 100),
                'event' => $event,
                'old_values' => json_encode(['marks' => rand(50, 80)]),
                'new_values' => json_encode(['marks' => rand(60, 95)]),
                'changes' => json_encode(['marks' => ['from' => rand(50, 80), 'to' => rand(60, 95)]]),
                'class_id' => $class->id,
                'subject_id' => null, // No specific subject for now
                'academic_year' => $currentYear,
                'term' => ['First Term', 'Second Term', 'Third Term'][array_rand(['First Term', 'Second Term', 'Third Term'])],
                'correction_reason' => $requiresApproval ? 'Marks correction due to calculation error' : null,
                'status' => $status,
                'approved_by' => $status === 'approved' ? $teachers->random()->id : null,
                'approved_at' => $status === 'approved' ? Carbon::now()->subDays(rand(1, 30)) : null,
                'rejected_by' => $status === 'rejected' ? $teachers->random()->id : null,
                'rejected_at' => $status === 'rejected' ? Carbon::now()->subDays(rand(1, 30)) : null,
                'rejection_reason' => $status === 'rejected' ? 'Insufficient documentation provided' : null,
                'tags' => json_encode(['marks_update', 'academic_record']),
                'description' => "Updated {$auditableType} record",
                'is_sensitive' => rand(0, 1) === 1,
                'requires_approval' => $requiresApproval,
                'created_at' => Carbon::now()->subDays(rand(1, 90)),
                'updated_at' => Carbon::now()->subDays(rand(1, 90)),
            ];
        }
        
        // Insert audit entries in chunks
        $chunks = array_chunk($auditEntries, 50);
        foreach ($chunks as $chunk) {
            \DB::table('audit_trails')->insert($chunk);
        }
        
        $this->command->info('Created ' . count($auditEntries) . ' sample audit trail entries');
    }
}