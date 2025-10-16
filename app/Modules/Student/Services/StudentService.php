<?php

namespace App\Modules\Student\Services;

use App\Modules\Student\Repositories\StudentRepository;
use App\Modules\Shared\Services\FileUploadService;
use App\Modules\Shared\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Student;
use App\Models\Classes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentService
{
    protected StudentRepository $studentRepository;
    protected FileUploadService $fileUploadService;
    protected NotificationService $notificationService;

    public function __construct(
        StudentRepository $studentRepository,
        FileUploadService $fileUploadService,
        NotificationService $notificationService
    ) {
        $this->studentRepository = $studentRepository;
        $this->fileUploadService = $fileUploadService;
        $this->notificationService = $notificationService;
    }

    /**
     * Get paginated students with filters
     */
    public function getStudents(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->studentRepository->getPaginatedWithFilters($filters, $perPage);
    }

    /**
     * Get student by ID with relationships
     */
    public function getStudentById(int $id): Student
    {
        return $this->studentRepository->findWithRelations($id, [
            'class',
            'section',
            'parent',
            'attendances' => function ($query) {
                $query->latest()->limit(10);
            },
            'examResults' => function ($query) {
                $query->latest()->limit(5);
            }
        ]);
    }

    /**
     * Create a new student
     */
    public function createStudent(array $data): Student
    {
        DB::beginTransaction();
        
        try {
            // Handle file uploads
            if (isset($data['photo'])) {
                $data['photo'] = $this->fileUploadService->uploadFile(
                    $data['photo'],
                    'students/photos',
                    ['jpg', 'jpeg', 'png'],
                    2048 // 2MB max
                );
            }

            if (isset($data['documents'])) {
                $data['documents'] = $this->fileUploadService->uploadMultipleFiles(
                    $data['documents'],
                    'students/documents',
                    ['pdf', 'jpg', 'jpeg', 'png'],
                    5120 // 5MB max per file
                );
            }

            // Generate student ID
            $data['student_id'] = $this->generateStudentId($data['class_id']);

            // Create student
            $student = $this->studentRepository->create($data);

            // Create parent/guardian record if provided
            if (isset($data['parent_data'])) {
                $student->parent()->create($data['parent_data']);
            }

            // Send welcome notification
            $this->notificationService->sendWelcomeNotification($student);

            DB::commit();
            
            Log::info('Student created successfully', ['student_id' => $student->id]);
            
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded files on error
            if (isset($data['photo'])) {
                $this->fileUploadService->deleteFile($data['photo']);
            }
            if (isset($data['documents'])) {
                foreach ($data['documents'] as $document) {
                    $this->fileUploadService->deleteFile($document);
                }
            }
            
            Log::error('Failed to create student', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            throw $e;
        }
    }

    /**
     * Update student information
     */
    public function updateStudent(int $id, array $data): Student
    {
        DB::beginTransaction();
        
        try {
            $student = $this->studentRepository->findOrFail($id);
            $oldPhoto = $student->photo;

            // Handle file uploads
            if (isset($data['photo'])) {
                $data['photo'] = $this->fileUploadService->uploadFile(
                    $data['photo'],
                    'students/photos',
                    ['jpg', 'jpeg', 'png'],
                    2048
                );
                
                // Delete old photo
                if ($oldPhoto) {
                    $this->fileUploadService->deleteFile($oldPhoto);
                }
            }

            if (isset($data['documents'])) {
                $data['documents'] = array_merge(
                    $student->documents ?? [],
                    $this->fileUploadService->uploadMultipleFiles(
                        $data['documents'],
                        'students/documents',
                        ['pdf', 'jpg', 'jpeg', 'png'],
                        5120
                    )
                );
            }

            // Update student
            $student = $this->studentRepository->update($id, $data);

            // Update parent/guardian record if provided
            if (isset($data['parent_data'])) {
                if ($student->parent) {
                    $student->parent->update($data['parent_data']);
                } else {
                    $student->parent()->create($data['parent_data']);
                }
            }

            DB::commit();
            
            Log::info('Student updated successfully', ['student_id' => $id]);
            
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update student', [
                'student_id' => $id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            throw $e;
        }
    }

    /**
     * Delete student
     */
    public function deleteStudent(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $student = $this->studentRepository->findOrFail($id);
            
            // Delete associated files
            if ($student->photo) {
                $this->fileUploadService->deleteFile($student->photo);
            }
            
            if ($student->documents) {
                foreach ($student->documents as $document) {
                    $this->fileUploadService->deleteFile($document);
                }
            }

            // Soft delete student
            $result = $this->studentRepository->delete($id);

            DB::commit();
            
            Log::info('Student deleted successfully', ['student_id' => $id]);
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete student', [
                'student_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Get available classes for student assignment
     */
    public function getAvailableClasses(): Collection
    {
        return Classes::where('status', 'active')
            ->with('sections')
            ->orderBy('grade_level')
            ->get();
    }

    /**
     * Get student academic records
     */
    public function getAcademicRecords(int $studentId): array
    {
        $student = $this->studentRepository->findOrFail($studentId);
        
        return [
            'attendance_summary' => $this->getAttendanceSummary($studentId),
            'exam_results' => $this->getExamResults($studentId),
            'fee_status' => $this->getFeeStatus($studentId),
            'disciplinary_records' => $this->getDisciplinaryRecords($studentId),
        ];
    }

    /**
     * Perform bulk actions on students
     */
    public function bulkAction(string $action, array $studentIds, array $params = []): array
    {
        DB::beginTransaction();
        
        try {
            $results = [];
            
            switch ($action) {
                case 'activate':
                    $results = $this->studentRepository->bulkUpdate($studentIds, ['status' => 'active']);
                    break;
                    
                case 'deactivate':
                    $results = $this->studentRepository->bulkUpdate($studentIds, ['status' => 'inactive']);
                    break;
                    
                case 'delete':
                    $results = $this->studentRepository->bulkDelete($studentIds);
                    break;
                    
                case 'promote':
                    $results = $this->promoteStudents($studentIds, $params['target_class_id']);
                    break;
                    
                default:
                    throw new \InvalidArgumentException("Invalid bulk action: {$action}");
            }

            DB::commit();
            
            Log::info('Bulk action completed', [
                'action' => $action,
                'student_count' => count($studentIds),
                'results' => $results
            ]);
            
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk action failed', [
                'action' => $action,
                'student_ids' => $studentIds,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate unique student ID
     */
    private function generateStudentId(int $classId): string
    {
        $class = Classes::findOrFail($classId);
        $year = date('Y');
        $prefix = strtoupper(substr($class->name, 0, 2));
        
        $lastStudent = $this->studentRepository->getLastStudentByClass($classId);
        $sequence = $lastStudent ? (int)substr($lastStudent->student_id, -4) + 1 : 1;
        
        return sprintf('%s%s%04d', $prefix, $year, $sequence);
    }

    /**
     * Get attendance summary for student
     */
    private function getAttendanceSummary(int $studentId): array
    {
        // Implementation for attendance summary
        return [
            'total_days' => 0,
            'present_days' => 0,
            'absent_days' => 0,
            'late_days' => 0,
            'attendance_percentage' => 0
        ];
    }

    /**
     * Get exam results for student
     */
    private function getExamResults(int $studentId): array
    {
        // Implementation for exam results
        return [];
    }

    /**
     * Get fee status for student
     */
    private function getFeeStatus(int $studentId): array
    {
        // Implementation for fee status
        return [
            'total_fee' => 0,
            'paid_amount' => 0,
            'pending_amount' => 0,
            'status' => 'pending'
        ];
    }

    /**
     * Get disciplinary records for student
     */
    private function getDisciplinaryRecords(int $studentId): array
    {
        // Implementation for disciplinary records
        return [];
    }

    /**
     * Promote students to next class
     */
    private function promoteStudents(array $studentIds, int $targetClassId): array
    {
        $results = [];
        
        foreach ($studentIds as $studentId) {
            $student = $this->studentRepository->findOrFail($studentId);
            $oldClassId = $student->class_id;
            
            $this->studentRepository->update($studentId, [
                'class_id' => $targetClassId,
                'promoted_at' => now()
            ]);
            
            $results[] = [
                'student_id' => $studentId,
                'old_class_id' => $oldClassId,
                'new_class_id' => $targetClassId
            ];
        }
        
        return $results;
    }
}