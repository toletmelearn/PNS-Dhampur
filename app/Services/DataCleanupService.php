<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\User;
use App\Models\Teacher;
use App\Models\DocumentVerification;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DataCleanupService
{
    /**
     * Find and handle orphaned student records
     */
    public function findOrphanedStudents()
    {
        // Students with invalid class_id (class doesn't exist)
        $orphanedStudents = Student::leftJoin('class_models', 'students.class_id', '=', 'class_models.id')
            ->whereNotNull('students.class_id')
            ->whereNull('class_models.id')
            ->select('students.*')
            ->get();

        // Students with null class_id but active status
        $studentsWithoutClass = Student::whereNull('class_id')
            ->where('status', 'active')
            ->get();

        return [
            'orphaned_students' => $orphanedStudents,
            'students_without_class' => $studentsWithoutClass,
            'total_issues' => $orphanedStudents->count() + $studentsWithoutClass->count()
        ];
    }

    /**
     * Fix orphaned student records
     */
    public function fixOrphanedStudents($action = 'set_inactive')
    {
        $results = $this->findOrphanedStudents();
        $fixed = 0;

        DB::beginTransaction();
        try {
            foreach ($results['orphaned_students'] as $student) {
                switch ($action) {
                    case 'set_inactive':
                        $student->update([
                            'status' => 'left',
                            'class_id' => null
                        ]);
                        break;
                    case 'delete':
                        $student->delete();
                        break;
                    case 'assign_default_class':
                        $defaultClass = ClassModel::where('is_active', true)->first();
                        if ($defaultClass) {
                            $student->update(['class_id' => $defaultClass->id]);
                        }
                        break;
                }
                $fixed++;
            }

            foreach ($results['students_without_class'] as $student) {
                if ($action === 'set_inactive') {
                    $student->update(['status' => 'left']);
                    $fixed++;
                } elseif ($action === 'assign_default_class') {
                    $defaultClass = ClassModel::where('is_active', true)->first();
                    if ($defaultClass) {
                        $student->update(['class_id' => $defaultClass->id]);
                        $fixed++;
                    }
                }
            }

            DB::commit();
            Log::info("Fixed {$fixed} orphaned student records using action: {$action}");
            
            return [
                'success' => true,
                'fixed_count' => $fixed,
                'action' => $action
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error fixing orphaned students: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Find duplicate student entries
     */
    public function findDuplicateStudents()
    {
        // Find duplicates by Aadhaar number
        $duplicatesByAadhaar = Student::select('aadhaar', DB::raw('COUNT(*) as count'))
            ->whereNotNull('aadhaar')
            ->where('aadhaar', '!=', '')
            ->groupBy('aadhaar')
            ->having('count', '>', 1)
            ->get();

        // Find duplicates by admission number
        $duplicatesByAdmission = Student::select('admission_no', DB::raw('COUNT(*) as count'))
            ->groupBy('admission_no')
            ->having('count', '>', 1)
            ->get();

        // Find potential duplicates by name and DOB
        $duplicatesByNameDob = Student::select('name', 'dob', DB::raw('COUNT(*) as count'))
            ->whereNotNull('dob')
            ->groupBy('name', 'dob')
            ->having('count', '>', 1)
            ->get();

        $duplicateDetails = [];

        // Get detailed duplicate records
        foreach ($duplicatesByAadhaar as $duplicate) {
            $students = Student::where('aadhaar', $duplicate->aadhaar)->get();
            $duplicateDetails['aadhaar'][] = [
                'field' => 'aadhaar',
                'value' => $duplicate->aadhaar,
                'count' => $duplicate->count,
                'students' => $students
            ];
        }

        foreach ($duplicatesByAdmission as $duplicate) {
            $students = Student::where('admission_no', $duplicate->admission_no)->get();
            $duplicateDetails['admission_no'][] = [
                'field' => 'admission_no',
                'value' => $duplicate->admission_no,
                'count' => $duplicate->count,
                'students' => $students
            ];
        }

        foreach ($duplicatesByNameDob as $duplicate) {
            $students = Student::where('name', $duplicate->name)
                ->where('dob', $duplicate->dob)
                ->get();
            $duplicateDetails['name_dob'][] = [
                'field' => 'name_dob',
                'value' => $duplicate->name . ' - ' . $duplicate->dob,
                'count' => $duplicate->count,
                'students' => $students
            ];
        }

        return $duplicateDetails;
    }

    /**
     * Merge duplicate student records
     */
    public function mergeDuplicateStudents($primaryStudentId, $duplicateStudentIds)
    {
        DB::beginTransaction();
        try {
            $primaryStudent = Student::findOrFail($primaryStudentId);
            $duplicateStudents = Student::whereIn('id', $duplicateStudentIds)->get();

            foreach ($duplicateStudents as $duplicate) {
                // Merge related records
                $this->mergeStudentRelatedRecords($primaryStudent, $duplicate);
                
                // Delete the duplicate
                $duplicate->delete();
            }

            DB::commit();
            Log::info("Merged duplicate students into primary student ID: {$primaryStudentId}");
            
            return [
                'success' => true,
                'primary_student' => $primaryStudent,
                'merged_count' => count($duplicateStudentIds)
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error merging duplicate students: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Merge related records from duplicate to primary student
     */
    private function mergeStudentRelatedRecords($primaryStudent, $duplicateStudent)
    {
        // Merge attendance records
        Attendance::where('student_id', $duplicateStudent->id)
            ->update(['student_id' => $primaryStudent->id]);

        // Merge fee records
        Fee::where('student_id', $duplicateStudent->id)
            ->update(['student_id' => $primaryStudent->id]);

        // Merge result records
        Result::where('student_id', $duplicateStudent->id)
            ->update(['student_id' => $primaryStudent->id]);

        // Merge document verifications
        DocumentVerification::where('student_id', $duplicateStudent->id)
            ->update(['student_id' => $primaryStudent->id]);

        // Merge documents (combine JSON arrays)
        if ($duplicateStudent->documents && $primaryStudent->documents) {
            $mergedDocuments = array_merge($primaryStudent->documents, $duplicateStudent->documents);
            $primaryStudent->update(['documents' => $mergedDocuments]);
        } elseif ($duplicateStudent->documents && !$primaryStudent->documents) {
            $primaryStudent->update(['documents' => $duplicateStudent->documents]);
        }
    }

    /**
     * Perform data consistency checks
     */
    public function performDataConsistencyChecks()
    {
        $issues = [];

        // Check for students with invalid user_id
        $studentsWithInvalidUser = Student::leftJoin('users', 'students.user_id', '=', 'users.id')
            ->whereNotNull('students.user_id')
            ->whereNull('users.id')
            ->select('students.*')
            ->get();

        if ($studentsWithInvalidUser->count() > 0) {
            $issues['invalid_user_references'] = [
                'count' => $studentsWithInvalidUser->count(),
                'records' => $studentsWithInvalidUser
            ];
        }

        // Check for classes without active teachers
        $classesWithoutTeachers = ClassModel::leftJoin('teachers', 'class_models.class_teacher_id', '=', 'teachers.id')
            ->where('class_models.is_active', true)
            ->whereNotNull('class_models.class_teacher_id')
            ->whereNull('teachers.id')
            ->select('class_models.*')
            ->get();

        if ($classesWithoutTeachers->count() > 0) {
            $issues['classes_without_teachers'] = [
                'count' => $classesWithoutTeachers->count(),
                'records' => $classesWithoutTeachers
            ];
        }

        // Check for attendance records with invalid student references
        $invalidAttendance = DB::table('attendances')
            ->leftJoin('students', 'attendances.student_id', '=', 'students.id')
            ->whereNull('students.id')
            ->select('attendances.*')
            ->get();

        if ($invalidAttendance->count() > 0) {
            $issues['invalid_attendance_references'] = [
                'count' => $invalidAttendance->count(),
                'records' => $invalidAttendance
            ];
        }

        // Check for fee records with invalid student references
        $invalidFees = DB::table('fees')
            ->leftJoin('students', 'fees.student_id', '=', 'students.id')
            ->whereNull('students.id')
            ->select('fees.*')
            ->get();

        if ($invalidFees->count() > 0) {
            $issues['invalid_fee_references'] = [
                'count' => $invalidFees->count(),
                'records' => $invalidFees
            ];
        }

        return $issues;
    }

    /**
     * Fix data consistency issues
     */
    public function fixDataConsistencyIssues($issues)
    {
        $fixed = 0;
        DB::beginTransaction();
        
        try {
            // Fix invalid user references
            if (isset($issues['invalid_user_references'])) {
                foreach ($issues['invalid_user_references']['records'] as $student) {
                    Student::where('id', $student->id)->update(['user_id' => null]);
                    $fixed++;
                }
            }

            // Fix classes without teachers
            if (isset($issues['classes_without_teachers'])) {
                foreach ($issues['classes_without_teachers']['records'] as $class) {
                    ClassModel::where('id', $class->id)->update(['class_teacher_id' => null]);
                    $fixed++;
                }
            }

            // Delete invalid attendance records
            if (isset($issues['invalid_attendance_references'])) {
                $attendanceIds = collect($issues['invalid_attendance_references']['records'])->pluck('id');
                DB::table('attendances')->whereIn('id', $attendanceIds)->delete();
                $fixed += count($attendanceIds);
            }

            // Delete invalid fee records
            if (isset($issues['invalid_fee_references'])) {
                $feeIds = collect($issues['invalid_fee_references']['records'])->pluck('id');
                DB::table('fees')->whereIn('id', $feeIds)->delete();
                $fixed += count($feeIds);
            }

            DB::commit();
            Log::info("Fixed {$fixed} data consistency issues");
            
            return [
                'success' => true,
                'fixed_count' => $fixed
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error fixing data consistency issues: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Archive old data based on criteria
     */
    public function archiveOldData($criteria = [])
    {
        $defaultCriteria = [
            'students_older_than_years' => 10,
            'attendance_older_than_years' => 5,
            'fees_older_than_years' => 7,
            'results_older_than_years' => 10
        ];

        $criteria = array_merge($defaultCriteria, $criteria);
        $archived = 0;

        DB::beginTransaction();
        try {
            // Archive old students (alumni status and very old)
            $oldStudentsCutoff = Carbon::now()->subYears($criteria['students_older_than_years']);
            $oldStudents = Student::where('status', 'alumni')
                ->where('updated_at', '<', $oldStudentsCutoff)
                ->get();

            foreach ($oldStudents as $student) {
                // Move to archive table or mark as archived
                $student->update(['status' => 'archived']);
                $archived++;
            }

            // Archive old attendance records
            $attendanceCutoff = Carbon::now()->subYears($criteria['attendance_older_than_years']);
            $archivedAttendance = DB::table('attendances')
                ->where('created_at', '<', $attendanceCutoff)
                ->delete();
            $archived += $archivedAttendance;

            // Archive old fee records (paid fees only)
            $feeCutoff = Carbon::now()->subYears($criteria['fees_older_than_years']);
            $archivedFees = DB::table('fees')
                ->where('status', 'paid')
                ->where('created_at', '<', $feeCutoff)
                ->delete();
            $archived += $archivedFees;

            DB::commit();
            Log::info("Archived {$archived} old records");
            
            return [
                'success' => true,
                'archived_count' => $archived,
                'criteria' => $criteria
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error archiving old data: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get comprehensive cleanup report
     */
    public function getCleanupReport()
    {
        $report = [
            'timestamp' => Carbon::now(),
            'orphaned_students' => $this->findOrphanedStudents(),
            'duplicate_students' => $this->findDuplicateStudents(),
            'consistency_issues' => $this->performDataConsistencyChecks(),
            'statistics' => [
                'total_students' => Student::count(),
                'active_students' => Student::where('status', 'active')->count(),
                'inactive_students' => Student::where('status', '!=', 'active')->count(),
                'students_with_class' => Student::whereNotNull('class_id')->count(),
                'students_without_class' => Student::whereNull('class_id')->count(),
                'total_classes' => ClassModel::count(),
                'active_classes' => ClassModel::where('is_active', true)->count(),
            ]
        ];

        return $report;
    }
}