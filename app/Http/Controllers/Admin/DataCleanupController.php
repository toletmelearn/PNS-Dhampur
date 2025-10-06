<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DataCleanupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DataCleanupController extends Controller
{
    protected $cleanupService;

    public function __construct(DataCleanupService $cleanupService)
    {
        $this->cleanupService = $cleanupService;
        $this->middleware('auth');
        $this->middleware('can:manage-data-cleanup');
    }

    /**
     * Display the data cleanup dashboard
     */
    public function index()
    {
        $report = $this->cleanupService->getCleanupReport();
        
        return view('admin.data-cleanup.index', compact('report'));
    }

    /**
     * Show orphaned records
     */
    public function orphanedRecords()
    {
        $orphanedData = $this->cleanupService->findOrphanedStudents();
        
        return view('admin.data-cleanup.orphaned', compact('orphanedData'));
    }

    /**
     * Fix orphaned records
     */
    public function fixOrphanedRecords(Request $request)
    {
        $request->validate([
            'action' => 'required|in:set_inactive,delete,assign_default_class'
        ]);

        $result = $this->cleanupService->fixOrphanedStudents($request->action);
        
        if ($result['success']) {
            return redirect()->back()->with('success', 
                "Successfully fixed {$result['fixed_count']} orphaned student records using action: {$result['action']}"
            );
        } else {
            return redirect()->back()->with('error', 
                "Error fixing orphaned records: {$result['error']}"
            );
        }
    }

    /**
     * Show duplicate records
     */
    public function duplicateRecords()
    {
        $duplicates = $this->cleanupService->findDuplicateStudents();
        
        return view('admin.data-cleanup.duplicates', compact('duplicates'));
    }

    /**
     * Merge duplicate records
     */
    public function mergeDuplicates(Request $request)
    {
        $request->validate([
            'primary_student_id' => 'required|exists:students,id',
            'duplicate_student_ids' => 'required|array',
            'duplicate_student_ids.*' => 'exists:students,id'
        ]);

        $result = $this->cleanupService->mergeDuplicateStudents(
            $request->primary_student_id,
            $request->duplicate_student_ids
        );
        
        if ($result['success']) {
            return redirect()->back()->with('success', 
                "Successfully merged {$result['merged_count']} duplicate records into primary student."
            );
        } else {
            return redirect()->back()->with('error', 
                "Error merging duplicate records: {$result['error']}"
            );
        }
    }

    /**
     * Show data consistency issues
     */
    public function consistencyChecks()
    {
        $issues = $this->cleanupService->performDataConsistencyChecks();
        
        return view('admin.data-cleanup.consistency', compact('issues'));
    }

    /**
     * Fix data consistency issues
     */
    public function fixConsistencyIssues(Request $request)
    {
        $issues = $this->cleanupService->performDataConsistencyChecks();
        $result = $this->cleanupService->fixDataConsistencyIssues($issues);
        
        if ($result['success']) {
            return redirect()->back()->with('success', 
                "Successfully fixed {$result['fixed_count']} consistency issues."
            );
        } else {
            return redirect()->back()->with('error', 
                "Error fixing consistency issues: {$result['error']}"
            );
        }
    }

    /**
     * Show archive and purge options
     */
    public function archiveData()
    {
        return view('admin.data-cleanup.archive');
    }

    /**
     * Perform data archiving
     */
    public function performArchive(Request $request)
    {
        $request->validate([
            'students_older_than_years' => 'required|integer|min:1|max:50',
            'attendance_older_than_years' => 'required|integer|min:1|max:20',
            'fees_older_than_years' => 'required|integer|min:1|max:20',
            'results_older_than_years' => 'required|integer|min:1|max:50'
        ]);

        $criteria = $request->only([
            'students_older_than_years',
            'attendance_older_than_years', 
            'fees_older_than_years',
            'results_older_than_years'
        ]);

        $result = $this->cleanupService->archiveOldData($criteria);
        
        if ($result['success']) {
            return redirect()->back()->with('success', 
                "Successfully archived {$result['archived_count']} old records."
            );
        } else {
            return redirect()->back()->with('error', 
                "Error archiving data: {$result['error']}"
            );
        }
    }

    /**
     * Generate and download cleanup report
     */
    public function downloadReport()
    {
        $report = $this->cleanupService->getCleanupReport();
        
        $filename = 'data_cleanup_report_' . now()->format('Y_m_d_H_i_s') . '.json';
        
        return response()->json($report)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * API endpoint for AJAX requests
     */
    public function apiReport()
    {
        $report = $this->cleanupService->getCleanupReport();
        
        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * API endpoint for orphaned records
     */
    public function apiOrphanedRecords()
    {
        $orphanedData = $this->cleanupService->findOrphanedStudents();
        
        return response()->json([
            'success' => true,
            'data' => $orphanedData
        ]);
    }

    /**
     * API endpoint for duplicate records
     */
    public function apiDuplicateRecords()
    {
        $duplicates = $this->cleanupService->findDuplicateStudents();
        
        return response()->json([
            'success' => true,
            'data' => $duplicates
        ]);
    }

    /**
     * API endpoint for consistency checks
     */
    public function apiConsistencyChecks()
    {
        $issues = $this->cleanupService->performDataConsistencyChecks();
        
        return response()->json([
            'success' => true,
            'data' => $issues
        ]);
    }
}