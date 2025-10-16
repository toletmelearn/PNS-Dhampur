<?php

namespace App\Modules\Student\Repositories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StudentRepository
{
    protected Student $model;

    public function __construct(Student $model)
    {
        $this->model = $model;
    }

    /**
     * Get paginated students with filters
     */
    public function getPaginatedWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['class', 'section', 'parent']);

        // Apply filters
        $this->applyFilters($query, $filters);

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find student by ID with relationships
     */
    public function findWithRelations(int $id, array $relations = []): Student
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    /**
     * Find student by ID or fail
     */
    public function findOrFail(int $id): Student
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create new student
     */
    public function create(array $data): Student
    {
        return $this->model->create($data);
    }

    /**
     * Update student
     */
    public function update(int $id, array $data): Student
    {
        $student = $this->findOrFail($id);
        $student->update($data);
        return $student->fresh();
    }

    /**
     * Delete student (soft delete)
     */
    public function delete(int $id): bool
    {
        $student = $this->findOrFail($id);
        return $student->delete();
    }

    /**
     * Get all active students
     */
    public function getActive(): Collection
    {
        return $this->model->where('status', 'active')
            ->with(['class', 'section'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get students by class
     */
    public function getByClass(int $classId): Collection
    {
        return $this->model->where('class_id', $classId)
            ->where('status', 'active')
            ->with(['section', 'parent'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get students by section
     */
    public function getBySection(int $classId, string $section): Collection
    {
        return $this->model->where('class_id', $classId)
            ->where('section', $section)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Search students by name or student ID
     */
    public function search(string $term): Collection
    {
        return $this->model->where(function ($query) use ($term) {
            $query->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('student_id', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%");
        })
        ->with(['class', 'section'])
        ->limit(20)
        ->get();
    }

    /**
     * Get last student by class for ID generation
     */
    public function getLastStudentByClass(int $classId): ?Student
    {
        return $this->model->where('class_id', $classId)
            ->orderBy('student_id', 'desc')
            ->first();
    }

    /**
     * Bulk update students
     */
    public function bulkUpdate(array $studentIds, array $data): int
    {
        return $this->model->whereIn('id', $studentIds)->update($data);
    }

    /**
     * Bulk delete students
     */
    public function bulkDelete(array $studentIds): int
    {
        return $this->model->whereIn('id', $studentIds)->delete();
    }

    /**
     * Get students with pending fees
     */
    public function getWithPendingFees(): Collection
    {
        return $this->model->whereHas('feeTransactions', function ($query) {
            $query->where('status', 'pending');
        })
        ->with(['class', 'section', 'feeTransactions' => function ($query) {
            $query->where('status', 'pending');
        }])
        ->get();
    }

    /**
     * Get students with low attendance
     */
    public function getWithLowAttendance(float $threshold = 75.0): Collection
    {
        return $this->model->whereHas('attendances', function ($query) use ($threshold) {
            $query->selectRaw('student_id, 
                (COUNT(CASE WHEN status = "present" THEN 1 END) * 100.0 / COUNT(*)) as attendance_percentage')
                ->groupBy('student_id')
                ->havingRaw('attendance_percentage < ?', [$threshold]);
        })
        ->with(['class', 'section'])
        ->get();
    }

    /**
     * Get birthday students for current month
     */
    public function getBirthdayStudents(): Collection
    {
        return $this->model->whereMonth('date_of_birth', date('m'))
            ->where('status', 'active')
            ->with(['class', 'section'])
            ->orderByRaw('DAY(date_of_birth)')
            ->get();
    }

    /**
     * Get students statistics
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        $active = $this->model->where('status', 'active')->count();
        $inactive = $this->model->where('status', 'inactive')->count();
        $graduated = $this->model->where('status', 'graduated')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'graduated' => $graduated,
            'by_class' => $this->getStudentsByClass(),
            'by_gender' => $this->getStudentsByGender(),
        ];
    }

    /**
     * Get students count by class
     */
    private function getStudentsByClass(): array
    {
        return $this->model->selectRaw('class_id, COUNT(*) as count')
            ->where('status', 'active')
            ->with('class:id,name')
            ->groupBy('class_id')
            ->get()
            ->pluck('count', 'class.name')
            ->toArray();
    }

    /**
     * Get students count by gender
     */
    private function getStudentsByGender(): array
    {
        return $this->model->selectRaw('gender, COUNT(*) as count')
            ->where('status', 'active')
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();
    }

    /**
     * Apply filters to query
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }

        if (!empty($filters['section'])) {
            $query->where('section', $filters['section']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('student_id', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['age_from'])) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$filters['age_from']]);
        }

        if (!empty($filters['age_to'])) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$filters['age_to']]);
        }
    }
}