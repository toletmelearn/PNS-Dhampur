<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\NewUser;
use App\Models\NewRole;
use App\Models\School;
use App\Models\UserSession;
use App\Models\UserRoleAssignment;
use App\Models\Attendance;
use App\Models\ClassDataApproval;
use App\Models\ExamPaperApproval;
use App\Models\AttendanceRegularization;
use App\Models\TeacherAbsence;
use App\Models\StudentVerification;

class DashboardController extends Controller
{
    /**
     * Super Admin dashboard view
     */
    public function superAdmin(Request $request)
    {
        $user = Auth::user();

        try {
            // Validate school schema to preempt missing columns
            $this->validateSchoolSchema();

            // Safe metrics with error handling
            $stats = [
                'total_users' => class_exists(NewUser::class) ? (int) NewUser::count() : 0,
                'total_schools' => class_exists(School::class) ? (int) School::count() : 0,
                'attendance_today' => class_exists(Attendance::class) ? (int) Attendance::whereDate('date', now()->toDateString())->count() : 0,
                'pending_approvals' => 0,
            ];

            // Active schools (robust to missing is_active column)
            try {
                if (class_exists(School::class)) {
                    if (Schema::hasColumn('schools', 'is_active')) {
                        $stats['active_schools'] = (int) School::where('is_active', true)->count();
                    } else {
                        $stats['active_schools'] = $stats['total_schools'];
                    }
                } else {
                    $stats['active_schools'] = 0;
                }
            } catch (\Throwable $e) {
                \Log::warning('Active schools count failed: ' . $e->getMessage());
                $stats['active_schools'] = $stats['total_schools'] ?? 0;
            }

            // Safe pending approvals calculation
            $pendingApprovals = 0;
            try {
                if (class_exists(ClassDataApproval::class)) {
                    $pendingApprovals += (int) (ClassDataApproval::getApprovalStatistics()['pending'] ?? 0);
                }
                if (class_exists(ExamPaperApproval::class)) {
                    $pendingApprovals += (int) (ExamPaperApproval::getApprovalStatistics()['total_pending'] ?? 0);
                }
                if (class_exists(AttendanceRegularization::class)) {
                    $pendingApprovals += (int) (AttendanceRegularization::pending()->count() ?? 0);
                }
            } catch (\Exception $e) {
                // Log error but don't break dashboard
                \Log::error('Pending approvals calculation failed: ' . $e->getMessage());
            }

            $stats['pending_approvals'] = $pendingApprovals;

            return view('dashboard.super-admin', compact('stats', 'user'));

        } catch (\Exception $e) {
            // Fallback if everything fails
            $fallbackStats = [
                'total_users' => 0,
                'total_schools' => 0,
                'attendance_today' => 0,
                'pending_approvals' => 0,
            ];
            return view('dashboard.super-admin', ['user' => $user, 'stats' => $fallbackStats]);
        }
    }

    /**
     * Validate schools table schema (prevents silent crashes and logs issues)
     */
    private function validateSchoolSchema(): bool
    {
        try {
            if (class_exists(School::class)) {
                $school = new School();
                $connection = $school->getConnection();
                $schema = $connection->getSchemaBuilder();

                if (!$schema->hasColumn('schools', 'is_active')) {
                    \Log::warning('Missing is_active column in schools table');
                    return false;
                }
            }
        } catch (\Throwable $e) {
            // Log but do not block dashboard rendering
            \Log::warning('Schema validation failed: ' . $e->getMessage());
        }
        return true;
    }
}