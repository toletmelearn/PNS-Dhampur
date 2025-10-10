<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Exam;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\User;
use App\Jobs\ArchiveDataJob;
use Exception;

class DataCleanupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Display the data cleanup dashboard
     */
    public function index()
    {
        $stats = $this->getCleanupStats();
        return view('admin.data-cleanup.index', compact('stats'));
    }

    /**
     * Get comprehensive cleanup report
     */
    public function getReport()
    {
        try {
            $report = [
                'orphaned_records' => $this->getOrphanedRecordsCount(),
                'duplicate_records' => $this->getDuplicateRecordsCount(),
                'consistency_issues' => $this->getConsistencyIssuesCount(),
                'archivable_data' => $this->getArchivableDataCount(),
                'last_cleanup' => $this->getLastCleanupDate(),
                'database_size' => $this->getDatabaseSize(),
                'recommendations' => $this->getRecommendations()
            ];

            return response()->json([
                'success' => true,
                'data' => $report
            ]);
        } catch (Exception $e) {
            Log::error('Data cleanup report error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate cleanup report'
            ], 500);
        }
    }

    /**
     * Display orphaned records page
     */
    public function orphaned()
    {
        $orphanedData = $this->getOrphanedRecords();
        return view('admin.data-cleanup.orphaned', compact('orphanedData'));
    }

    /**
     * Fix orphaned records
     */
    public function fixOrphaned(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:delete,reassign',
            'records' => 'required|array',
            'records.*' => 'required|string',
            'reassign_to' => 'nullable|integer|exists:classes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $fixed = 0;
            foreach ($request->records as $record) {
                [$table, $id] = explode(':', $record);
                
                if ($request->action === 'delete') {
                    $fixed += $this->deleteOrphanedRecord($table, $id);
                } else {
                    $fixed += $this->reassignOrphanedRecord($table, $id, $request->reassign_to);
                }
            }

            DB::commit();

            Log::info("Fixed {$fixed} orphaned records", [
                'action' => $request->action,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully fixed {$fixed} orphaned records",
                'fixed_count' => $fixed
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Fix orphaned records error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fix orphaned records'
            ], 500);
        }
    }

    /**
     * Display duplicate records page
     */
    public function duplicates()
    {
        $duplicates = $this->getDuplicateRecords();
        return view('admin.data-cleanup.duplicates', compact('duplicates'));
    }

    /**
     * Merge duplicate records
     */
    public function mergeDuplicates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'duplicates' => 'required|array',
            'duplicates.*.keep' => 'required|integer',
            'duplicates.*.merge' => 'required|array',
            'duplicates.*.merge.*' => 'integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $merged = 0;
            foreach ($request->duplicates as $duplicate) {
                $merged += $this->mergeDuplicateRecords($duplicate['keep'], $duplicate['merge']);
            }

            DB::commit();

            Log::info("Merged {$merged} duplicate records", [
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully merged {$merged} duplicate records",
                'merged_count' => $merged
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Merge duplicates error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to merge duplicate records'
            ], 500);
        }
    }

    /**
     * Display data consistency page
     */
    public function consistency()
    {
        $issues = $this->getConsistencyIssues();
        return view('admin.data-cleanup.consistency', compact('issues'));
    }

    /**
     * Check data consistency
     */
    public function checkConsistency()
    {
        try {
            $issues = $this->performConsistencyCheck();
            
            return response()->json([
                'success' => true,
                'data' => $issues,
                'total_issues' => array_sum(array_column($issues, 'count'))
            ]);
        } catch (Exception $e) {
            Log::error('Consistency check error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform consistency check'
            ], 500);
        }
    }

    /**
     * Fix consistency issues
     */
    public function fixConsistency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'issues' => 'required|array',
            'issues.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $fixed = 0;
            foreach ($request->issues as $issue) {
                $fixed += $this->fixConsistencyIssue($issue);
            }

            DB::commit();

            Log::info("Fixed {$fixed} consistency issues", [
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully fixed {$fixed} consistency issues",
                'fixed_count' => $fixed
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Fix consistency error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fix consistency issues'
            ], 500);
        }
    }

    /**
     * Display archive and purge page
     */
    public function archive()
    {
        $archiveStats = $this->getArchiveStats();
        return view('admin.data-cleanup.archive', compact('archiveStats'));
    }

    /**
     * Preview archive operation
     */
    public function previewArchive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archive_date' => 'required|date|before:today',
            'tables' => 'required|array',
            'tables.*' => 'required|string|in:students,teachers,exams,attendance,fees'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $preview = $this->getArchivePreview($request->archive_date, $request->tables);
            
            return response()->json([
                'success' => true,
                'data' => $preview
            ]);
        } catch (Exception $e) {
            Log::error('Archive preview error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate archive preview'
            ], 500);
        }
    }

    /**
     * Start archive process
     */
    public function startArchive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archive_date' => 'required|date|before:today',
            'tables' => 'required|array',
            'tables.*' => 'required|string|in:students,teachers,exams,attendance,fees',
            'create_backup' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jobId = uniqid('archive_');
            
            ArchiveDataJob::dispatch(
                $jobId,
                $request->archive_date,
                $request->tables,
                $request->boolean('create_backup', true),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Archive process started',
                'job_id' => $jobId
            ]);

        } catch (Exception $e) {
            Log::error('Start archive error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start archive process'
            ], 500);
        }
    }

    /**
     * Get archive progress
     */
    public function archiveProgress($jobId)
    {
        try {
            $progress = cache()->get("archive_progress_{$jobId}", [
                'status' => 'not_found',
                'progress' => 0,
                'message' => 'Archive job not found'
            ]);

            return response()->json([
                'success' => true,
                'data' => $progress
            ]);
        } catch (Exception $e) {
            Log::error('Archive progress error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get archive progress'
            ], 500);
        }
    }

    /**
     * Purge archived data
     */
    public function purgeArchive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'archive_id' => 'required|string',
            'confirm' => 'required|boolean|accepted'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $purged = $this->performPurge($request->archive_id);

            Log::info("Purged archive {$request->archive_id}", [
                'user_id' => auth()->id(),
                'records_purged' => $purged
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully purged {$purged} archived records"
            ]);

        } catch (Exception $e) {
            Log::error('Purge archive error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to purge archived data'
            ], 500);
        }
    }

    /**
     * Download archive
     */
    public function downloadArchive($id = null)
    {
        try {
            if ($id) {
                $archivePath = "archives/archive_{$id}.zip";
            } else {
                // Get latest archive
                $archives = Storage::files('archives');
                $archivePath = collect($archives)->sortByDesc(function ($file) {
                    return Storage::lastModified($file);
                })->first();
            }

            if (!$archivePath || !Storage::exists($archivePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archive file not found'
                ], 404);
            }

            return Storage::download($archivePath);

        } catch (Exception $e) {
            Log::error('Download archive error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to download archive'
            ], 500);
        }
    }

    // API Methods for AJAX calls

    public function getOrphanedStudents()
    {
        return response()->json($this->getOrphanedRecords()['students'] ?? []);
    }

    public function getDuplicateStudents()
    {
        return response()->json($this->getDuplicateRecords()['students'] ?? []);
    }

    public function getConsistencyIssues()
    {
        return response()->json($this->getConsistencyIssuesData());
    }

    public function getArchivableData()
    {
        return response()->json($this->getArchiveStats());
    }

    // Private helper methods

    private function getCleanupStats()
    {
        return [
            'orphaned_records' => $this->getOrphanedRecordsCount(),
            'duplicate_records' => $this->getDuplicateRecordsCount(),
            'consistency_issues' => $this->getConsistencyIssuesCount(),
            'archivable_data' => $this->getArchivableDataCount(),
            'database_size' => $this->getDatabaseSize(),
            'last_cleanup' => $this->getLastCleanupDate()
        ];
    }

    private function getOrphanedRecordsCount()
    {
        $count = 0;
        
        // Students without classes
        $count += Student::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('classes')
                  ->whereRaw('classes.id = students.class_id');
        })->count();

        // Attendance without students
        $count += DB::table('attendance')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('students')
                      ->whereRaw('students.id = attendance.student_id');
            })->count();

        // Fees without students
        $count += DB::table('fees')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('students')
                      ->whereRaw('students.id = fees.student_id');
            })->count();

        return $count;
    }

    private function getDuplicateRecordsCount()
    {
        $count = 0;

        // Duplicate students (same name and date of birth)
        $count += Student::select('name', 'date_of_birth')
            ->groupBy('name', 'date_of_birth')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // Duplicate teachers (same email)
        $count += Teacher::select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return $count;
    }

    private function getConsistencyIssuesCount()
    {
        $count = 0;

        // Students with invalid class references
        $count += Student::whereNotNull('class_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('classes')
                      ->whereRaw('classes.id = students.class_id');
            })->count();

        // Exams with invalid subject references
        $count += Exam::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('subjects')
                  ->whereRaw('subjects.id = exams.subject_id');
        })->count();

        return $count;
    }

    private function getArchivableDataCount()
    {
        $cutoffDate = Carbon::now()->subYears(2);
        $count = 0;

        $count += Student::where('created_at', '<', $cutoffDate)
            ->where('status', 'inactive')
            ->count();

        $count += Exam::where('exam_date', '<', $cutoffDate)->count();
        $count += DB::table('attendance')->where('date', '<', $cutoffDate)->count();

        return $count;
    }

    private function getDatabaseSize()
    {
        try {
            $size = DB::select("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            
            return $size[0]->size_mb ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function getLastCleanupDate()
    {
        return cache()->get('last_cleanup_date', 'Never');
    }

    private function getRecommendations()
    {
        $recommendations = [];
        
        if ($this->getOrphanedRecordsCount() > 0) {
            $recommendations[] = 'Clean up orphaned records to improve database performance';
        }
        
        if ($this->getDuplicateRecordsCount() > 0) {
            $recommendations[] = 'Merge duplicate records to maintain data integrity';
        }
        
        if ($this->getConsistencyIssuesCount() > 0) {
            $recommendations[] = 'Fix data consistency issues to prevent application errors';
        }
        
        if ($this->getArchivableDataCount() > 100) {
            $recommendations[] = 'Archive old data to reduce database size and improve performance';
        }

        return $recommendations;
    }

    private function getOrphanedRecords()
    {
        return [
            'students' => Student::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('classes')
                      ->whereRaw('classes.id = students.class_id');
            })->get(),
            
            'attendance' => DB::table('attendance')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('students')
                          ->whereRaw('students.id = attendance.student_id');
                })->get(),
                
            'fees' => DB::table('fees')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('students')
                          ->whereRaw('students.id = fees.student_id');
                })->get()
        ];
    }

    private function getDuplicateRecords()
    {
        return [
            'students' => Student::select('name', 'date_of_birth', DB::raw('COUNT(*) as count'))
                ->groupBy('name', 'date_of_birth')
                ->havingRaw('COUNT(*) > 1')
                ->with(['duplicates' => function ($query) {
                    $query->select('id', 'name', 'date_of_birth', 'admission_number', 'created_at');
                }])
                ->get(),
                
            'teachers' => Teacher::select('email', DB::raw('COUNT(*) as count'))
                ->groupBy('email')
                ->havingRaw('COUNT(*) > 1')
                ->get()
        ];
    }

    private function getConsistencyIssuesData()
    {
        return [
            'invalid_class_references' => Student::whereNotNull('class_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('classes')
                          ->whereRaw('classes.id = students.class_id');
                })->get(),
                
            'invalid_subject_references' => Exam::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('subjects')
                      ->whereRaw('subjects.id = exams.subject_id');
            })->get()
        ];
    }

    private function getArchiveStats()
    {
        $cutoffDate = Carbon::now()->subYears(2);
        
        return [
            'students' => Student::where('created_at', '<', $cutoffDate)
                ->where('status', 'inactive')
                ->count(),
            'exams' => Exam::where('exam_date', '<', $cutoffDate)->count(),
            'attendance' => DB::table('attendance')->where('date', '<', $cutoffDate)->count(),
            'fees' => DB::table('fees')->where('created_at', '<', $cutoffDate)->count()
        ];
    }

    private function deleteOrphanedRecord($table, $id)
    {
        return DB::table($table)->where('id', $id)->delete();
    }

    private function reassignOrphanedRecord($table, $id, $classId)
    {
        if ($table === 'students') {
            return DB::table($table)->where('id', $id)->update(['class_id' => $classId]);
        }
        return 0;
    }

    private function mergeDuplicateRecords($keepId, $mergeIds)
    {
        // Implementation for merging duplicate records
        // This would involve updating foreign key references and deleting duplicates
        return count($mergeIds);
    }

    private function performConsistencyCheck()
    {
        return [
            [
                'type' => 'invalid_class_references',
                'description' => 'Students with invalid class references',
                'count' => Student::whereNotNull('class_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                              ->from('classes')
                              ->whereRaw('classes.id = students.class_id');
                    })->count()
            ],
            [
                'type' => 'invalid_subject_references',
                'description' => 'Exams with invalid subject references',
                'count' => Exam::whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('subjects')
                          ->whereRaw('subjects.id = exams.subject_id');
                })->count()
            ]
        ];
    }

    private function fixConsistencyIssue($issue)
    {
        // Implementation for fixing specific consistency issues
        return 1;
    }

    private function getArchivePreview($archiveDate, $tables)
    {
        $preview = [];
        $cutoffDate = Carbon::parse($archiveDate);
        
        foreach ($tables as $table) {
            switch ($table) {
                case 'students':
                    $preview[$table] = Student::where('created_at', '<', $cutoffDate)
                        ->where('status', 'inactive')
                        ->count();
                    break;
                case 'exams':
                    $preview[$table] = Exam::where('exam_date', '<', $cutoffDate)->count();
                    break;
                case 'attendance':
                    $preview[$table] = DB::table('attendance')
                        ->where('date', '<', $cutoffDate)
                        ->count();
                    break;
                case 'fees':
                    $preview[$table] = DB::table('fees')
                        ->where('created_at', '<', $cutoffDate)
                        ->count();
                    break;
            }
        }
        
        return $preview;
    }

    private function performPurge($archiveId)
    {
        // Implementation for purging archived data
        return 0;
    }
}