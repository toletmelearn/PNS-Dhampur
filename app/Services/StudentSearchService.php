<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ClassModel;
use App\Helpers\SecurityHelper;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class StudentSearchService
{
    /**
     * Apply comprehensive search and filters to student query
     */
    public function search(Request $request): Builder
    {
        // Comprehensive eager loading to prevent N+1 queries
        $query = Student::with([
            'classModel:id,name,section,is_active',
            'user:id,name,email,phone,address,gender',
            'attendance' => function($query) {
                $query->select('id', 'student_id', 'date', 'status')
                      ->whereMonth('date', now()->month);
            },
            'fees' => function($query) {
                $query->select('id', 'student_id', 'amount', 'paid_amount', 'due_date')
                      ->where('due_date', '>=', now());
            }
        ])
        // Add comprehensive counts to prevent N+1 queries
        ->withCount([
            'attendance as present_count' => function($query) {
                $query->where('status', 'present')
                      ->whereMonth('date', now()->month);
            },
            'attendance as total_attendance_count' => function($query) {
                $query->whereMonth('date', now()->month);
            },
            'fees as pending_fees_count' => function($query) {
                $query->where('due_date', '<', now())
                      ->whereColumn('paid_amount', '<', 'amount');
            }
        ])
        ->withSum('fees as total_fees_sum', 'amount')
        ->withSum('fees as paid_fees_sum', 'paid_amount');

        // Basic text search across multiple fields
        if ($request->filled('search')) {
            $this->applyTextSearch($query, $request->search);
        }

        // Class filter
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Gender filter
        if ($request->filled('gender')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        // Age range filters
        if ($request->filled('age_min') || $request->filled('age_max')) {
            $this->applyAgeFilters($query, $request);
        }

        // Date of birth range filters
        if ($request->filled('dob_from') || $request->filled('dob_to')) {
            $this->applyDobFilters($query, $request);
        }

        // Admission date range filters
        if ($request->filled('admission_from') || $request->filled('admission_to')) {
            $this->applyAdmissionDateFilters($query, $request);
        }

        // Verification status filter
        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        // Verified filter
        if ($request->filled('verified')) {
            $query->where('verified', $request->boolean('verified'));
        }

        // Has Aadhaar filter
        if ($request->filled('has_aadhaar')) {
            if ($request->boolean('has_aadhaar')) {
                $query->whereNotNull('aadhaar');
            } else {
                $query->whereNull('aadhaar');
            }
        }

        // Has documents filter
        if ($request->filled('has_documents')) {
            if ($request->boolean('has_documents')) {
                $query->whereNotNull('documents');
            } else {
                $query->whereNull('documents');
            }
        }

        // Father's name search
        if ($request->filled('father_name')) {
            $query->where('father_name', 'like', SecurityHelper::buildLikePattern($request->father_name));
        }

        // Mother's name search
        if ($request->filled('mother_name')) {
            $query->where('mother_name', 'like', SecurityHelper::buildLikePattern($request->mother_name));
        }

        // Contact number search
        if ($request->filled('contact_number')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('phone', 'like', SecurityHelper::buildLikePattern($request->contact_number));
            });
        }

        // Email search
        if ($request->filled('email')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('email', 'like', SecurityHelper::buildLikePattern($request->email));
            });
        }

        // Address search
        if ($request->filled('address')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('address', 'like', SecurityHelper::buildLikePattern($request->address));
            });
        }

        // Multiple class filter
        if ($request->filled('class_ids') && is_array($request->class_ids)) {
            $query->whereIn('class_id', $request->class_ids);
        }

        // Academic year filter (if stored in meta or separate field)
        if ($request->filled('academic_year')) {
            $query->where(function ($q) use ($request) {
                $q->whereJsonContains('meta->academic_year', $request->academic_year)
                  ->orWhere('academic_year', $request->academic_year);
            });
        }

        return $query;
    }

    /**
     * Apply text search across multiple fields
     */
    private function applyTextSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $safeSearch = SecurityHelper::buildLikePattern($search);
            $q->where('name', 'like', $safeSearch)
              ->orWhere('admission_no', 'like', $safeSearch)
              ->orWhere('aadhaar', 'like', $safeSearch)
              ->orWhere('father_name', 'like', $safeSearch)
              ->orWhere('mother_name', 'like', $safeSearch)
              ->orWhereHas('user', function ($userQuery) use ($safeSearch) {
                  $userQuery->where('email', 'like', $safeSearch)
                           ->orWhere('phone', 'like', $safeSearch)
                           ->orWhere('address', 'like', $safeSearch);
              })
              ->orWhereHas('classModel', function ($classQuery) use ($safeSearch) {
                  $classQuery->where('name', 'like', $safeSearch)
                            ->orWhere('section', 'like', $safeSearch);
              });
        });
    }

    /**
     * Apply age range filters
     */
    private function applyAgeFilters(Builder $query, Request $request): void
    {
        if ($request->filled('age_min')) {
            $maxDate = Carbon::now()->subYears($request->age_min)->endOfYear();
            $query->where('dob', '<=', $maxDate);
        }

        if ($request->filled('age_max')) {
            $minDate = Carbon::now()->subYears($request->age_max + 1)->startOfYear();
            $query->where('dob', '>=', $minDate);
        }
    }

    /**
     * Apply date of birth range filters
     */
    private function applyDobFilters(Builder $query, Request $request): void
    {
        if ($request->filled('dob_from')) {
            $query->where('dob', '>=', $request->dob_from);
        }

        if ($request->filled('dob_to')) {
            $query->where('dob', '<=', $request->dob_to);
        }
    }

    /**
     * Apply admission date range filters
     */
    private function applyAdmissionDateFilters(Builder $query, Request $request): void
    {
        if ($request->filled('admission_from')) {
            $query->where('created_at', '>=', $request->admission_from);
        }

        if ($request->filled('admission_to')) {
            $query->where('created_at', '<=', $request->admission_to . ' 23:59:59');
        }
    }

    /**
     * Get filter options for dropdowns
     */
    public function getFilterOptions(): array
    {
        return [
            'classes' => ClassModel::select('id', 'name', 'section')
                                  ->where('is_active', true)
                                  ->orderBy('name')
                                  ->orderBy('section')
                                  ->get(),
            'statuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'left' => 'Left',
                'alumni' => 'Alumni'
            ],
            'genders' => [
                'male' => 'Male',
                'female' => 'Female',
                'other' => 'Other'
            ],
            'verification_statuses' => [
                'pending' => 'Pending',
                'verified' => 'Verified',
                'rejected' => 'Rejected',
                'incomplete' => 'Incomplete'
            ],
            'academic_years' => $this->getAcademicYears()
        ];
    }

    /**
     * Get available academic years
     */
    private function getAcademicYears(): array
    {
        $currentYear = Carbon::now()->year;
        $years = [];
        
        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
            $years["{$i}-" . ($i + 1)] = "{$i}-" . ($i + 1);
        }
        
        return $years;
    }

    /**
     * Apply sorting to the query
     */
    public function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        // Validate sort order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'admission_no':
                $query->orderBy('admission_no', $sortOrder);
                break;
            case 'class':
                $query->join('class_models', 'students.class_id', '=', 'class_models.id')
                      ->orderBy('class_models.name', $sortOrder)
                      ->orderBy('class_models.section', $sortOrder)
                      ->select('students.*');
                break;
            case 'dob':
                $query->orderBy('dob', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'verification_status':
                $query->orderBy('verification_status', $sortOrder);
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        return $query;
    }

    /**
     * Get search statistics
     */
    public function getSearchStats(Builder $query): array
    {
        $baseQuery = clone $query;
        
        return [
            'total_results' => $baseQuery->count(),
            'verified_count' => (clone $baseQuery)->where('verified', true)->count(),
            'unverified_count' => (clone $baseQuery)->where('verified', false)->count(),
            'active_count' => (clone $baseQuery)->where('status', 'active')->count(),
            'inactive_count' => (clone $baseQuery)->where('status', 'inactive')->count(),
            'class_distribution' => (clone $baseQuery)
                ->join('class_models', 'students.class_id', '=', 'class_models.id')
                ->selectRaw('class_models.name as class_name, class_models.section, COUNT(*) as count')
                ->groupBy('class_models.id', 'class_models.name', 'class_models.section')
                ->orderBy('class_models.name')
                ->orderBy('class_models.section')
                ->get()
        ];
    }

    /**
     * Export search results
     */
    public function exportResults(Builder $query, string $format = 'csv'): array
    {
        $students = $query->with(['classModel', 'user', 'attendance'])->get();
        
        $data = $students->map(function ($student) {
            return [
                'Name' => $student->name,
                'Admission No' => $student->admission_no,
                'Class' => $student->classModel ? $student->classModel->name . ' - ' . $student->classModel->section : 'N/A',
                'Father Name' => $student->father_name,
                'Mother Name' => $student->mother_name,
                'Date of Birth' => $student->dob ? $student->dob->format('Y-m-d') : 'N/A',
                'Aadhaar' => $student->aadhaar,
                'Status' => ucfirst($student->status),
                'Verified' => $student->verified ? 'Yes' : 'No',
                'Email' => $student->user->email ?? 'N/A',
                'Phone' => $student->user->phone ?? 'N/A',
                'Address' => $student->user->address ?? 'N/A',
                'Created At' => $student->created_at->format('Y-m-d H:i:s')
            ];
        });

        return $data->toArray();
    }
}